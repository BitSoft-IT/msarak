/**
 * Assessment Journey Controller (Issue F-03)
 * Vanilla JavaScript implementation strictly following contracts H-03 and C-03.
 * No RIASEC codes, formulas, or sensitive scoring calculations are exposed.
 */

const RATING_LEVELS = [
    { value: 2, label: 'يشبهني جدًا', emoji: '😊' },
    { value: 1, label: 'يشبهني', emoji: '🙂' },
    { value: 0, label: 'محايد / غير متأكد', emoji: '😐' },
    { value: -1, label: 'لا يشبهني', emoji: '🙁' },
    { value: -2, label: 'لا يشبهني إطلاقًا', emoji: '😞' },
];

class AssessmentJourney {
    constructor(appElement) {
        this.app = appElement;
        this.sessionId = this.app.dataset.sessionId;
        this.completeUrl = this.app.dataset.completeUrl;
        this.saveBaseUrl = this.app.dataset.saveBaseUrl;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // State
        this.questions = [];
        this.answers = {}; // question_id -> { primary_option_id, none_selected, unable_to_judge, ratings: {} }
        this.currentIndex = 0;
        this.saveStatus = 'idle'; // idle | saving | saved | error
        this.saveTimeout = null;
        this.isCompleted = false;

        // Pending conflict state
        this.pendingConflict = null;

        // DOM elements cache
        this.cacheElements();
        this.bindEvents();
        this.initData();
    }

    cacheElements() {
        this.currentPositionNum = document.getElementById('current-position-num');
        this.totalQuestionsNum = document.getElementById('total-questions-num');
        this.processedCountNum = document.getElementById('processed-count-num');
        this.progressBarFill = document.getElementById('progress-bar-fill');
        this.progressBarContainer = document.getElementById('progress-bar-container');

        this.scenarioBadge = document.getElementById('scenario-badge');
        this.badgeNum = document.getElementById('badge-num');
        this.scenarioText = document.getElementById('scenario-text');
        this.optionsContainer = document.getElementById('options-container');

        this.noneFitRadio = document.getElementById('none-fit-radio');
        this.noneFitCard = document.getElementById('none-fit-card');
        this.cannotJudgeRadio = document.getElementById('cannot-judge-radio');
        this.cannotJudgeCard = document.getElementById('cannot-judge-card');

        this.saveStatusIndicator = document.getElementById('save-status-indicator');
        this.saveStatusText = document.getElementById('save-status-text');

        this.errorAlert = document.getElementById('error-alert');
        this.errorAlertMessage = document.getElementById('error-alert-message');
        this.retrySaveBtn = document.getElementById('retry-save-btn');

        this.prevBtn = document.getElementById('prev-btn');
        this.nextBtn = document.getElementById('next-btn');
        this.completeBtn = document.getElementById('complete-btn');
        this.questionsNavGrid = document.getElementById('questions-nav-grid');

        // Conflict Modal
        this.conflictModal = document.getElementById('conflict-modal');
        this.conflictKeepBtn = document.getElementById('conflict-keep-btn');
        this.conflictReviewBtn = document.getElementById('conflict-review-btn');

        // Completion Modal
        this.completionModal = document.getElementById('completion-modal');
        this.completionConfirmBtn = document.getElementById('completion-confirm-btn');
        this.completionCancelBtn = document.getElementById('completion-cancel-btn');
        this.completionModalError = document.getElementById('completion-modal-error');
    }

    bindEvents() {
        this.prevBtn.addEventListener('click', () => this.goToPrevious());
        this.nextBtn.addEventListener('click', () => this.goToNext());
        this.completeBtn.addEventListener('click', () => this.openCompletionModal());

        this.noneFitRadio.addEventListener('change', () => this.handleSpecialChoice('none_selected'));
        this.cannotJudgeRadio.addEventListener('change', () => this.handleSpecialChoice('unable_to_judge'));

        this.retrySaveBtn.addEventListener('click', () => this.saveCurrentAnswer());

        // Conflict modal handlers
        this.conflictKeepBtn.addEventListener('click', () => this.resolveConflict(true));
        this.conflictReviewBtn.addEventListener('click', () => this.resolveConflict(false));

        // Completion modal handlers
        this.completionConfirmBtn.addEventListener('click', () => this.submitCompletion());
        this.completionCancelBtn.addEventListener('click', () => this.closeCompletionModal());
    }

    initData() {
        const initialScript = document.getElementById('assessment-initial-data');
        if (initialScript && initialScript.textContent.trim()) {
            try {
                const initialData = JSON.parse(initialScript.textContent);
                this.loadPayload(initialData);
                return;
            } catch (err) {
                console.error('Failed to parse embedded initial assessment data', err);
            }
        }

        // Fallback: Fetch via JSON from backend
        this.fetchSessionData();
    }

    async fetchSessionData() {
        this.setSaveStatus('loading', 'جارٍ تحميل جلسة التقييم…');
        try {
            const response = await fetch(this.app.dataset.sessionUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
            });

            if (!response.ok) {
                throw new Error('Failed to load session');
            }

            const json = await response.json();
            this.loadPayload(json.data);
        } catch (err) {
            this.showError('تعذر الاتصال. تأكد من اتصالك بالإنترنت ثم حاول مرة أخرى.');
        }
    }

    loadPayload(data) {
        if (!data || !data.questions) return;

        this.questions = data.questions.sort((a, b) => a.position - b.position);
        this.isCompleted = data.session?.status === 'completed';

        // Preload saved answers
        this.answers = {};
        if (Array.isArray(data.saved_answers)) {
            data.saved_answers.forEach((ans) => {
                const ratingsMap = {};
                if (Array.isArray(ans.ratings)) {
                    ans.ratings.forEach((r) => {
                        ratingsMap[r.option_id] = r.rating;
                    });
                }

                this.answers[ans.question_id] = {
                    primary_option_id: ans.primary_option_id,
                    none_selected: Boolean(ans.none_selected),
                    unable_to_judge: Boolean(ans.unable_to_judge),
                    ratings: ratingsMap,
                };
            });
        }

        // Determine starting position:
        // Contract states: current_position indicates first unanswered position (1-indexed)
        const startingPosition = data.progress?.current_position || 1;
        const targetIndex = this.questions.findIndex((q) => q.position === startingPosition);
        this.currentIndex = targetIndex >= 0 ? targetIndex : 0;

        this.renderNavGrid();
        this.renderCurrentQuestion();
        this.updateProgress();

        if (this.isCompleted) {
            this.disableInputsForCompletedSession();
        }
    }

    getCurrentQuestion() {
        return this.questions[this.currentIndex] || null;
    }

    getCurrentAnswer() {
        const question = this.getCurrentQuestion();
        if (!question) return null;

        if (!this.answers[question.question_id]) {
            this.answers[question.question_id] = {
                primary_option_id: null,
                none_selected: false,
                unable_to_judge: false,
                ratings: {},
            };
        }

        return this.answers[question.question_id];
    }

    renderCurrentQuestion() {
        const question = this.getCurrentQuestion();
        if (!question) return;

        const currentAnswer = this.getCurrentAnswer();
        this.hideError();

        // Update header & badges
        this.badgeNum.textContent = question.position;
        this.currentPositionNum.textContent = question.position;
        this.totalQuestionsNum.textContent = this.questions.length;
        this.scenarioText.textContent = question.scenario;

        // Reset radio states
        this.noneFitRadio.checked = currentAnswer.none_selected;
        this.cannotJudgeRadio.checked = currentAnswer.unable_to_judge;
        this.updateSpecialCardStyles();

        // Render the 4 options
        this.optionsContainer.innerHTML = '';
        const hasPrimary = currentAnswer.primary_option_id !== null;

        question.options.forEach((opt, idx) => {
            const isPrimary = currentAnswer.primary_option_id === opt.option_id;
            const currentRating = currentAnswer.ratings[opt.option_id];

            const card = document.createElement('div');
            card.className = `option-card rounded-xl border p-4 transition-all ${
                isPrimary
                    ? 'border-brand-600 bg-brand-50/40 ring-1 ring-brand-600 shadow-sm'
                    : 'border-slate-200 bg-white hover:border-slate-300'
            }`;
            card.dataset.optionId = opt.option_id;

            // Option selection row
            const topRow = document.createElement('div');
            topRow.className = 'flex items-start gap-3 cursor-pointer';

            const radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'primary_option';
            radio.value = opt.option_id;
            radio.id = `opt-radio-${opt.option_id}`;
            radio.checked = isPrimary;
            radio.className = 'mt-1 h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-600 cursor-pointer';

            const label = document.createElement('label');
            label.htmlFor = radio.id;
            label.className = 'flex-1 text-sm font-semibold text-slate-900 cursor-pointer leading-relaxed';
            label.textContent = opt.option_text;

            topRow.appendChild(radio);
            topRow.appendChild(label);
            card.appendChild(topRow);

            topRow.addEventListener('click', (e) => {
                if (e.target !== radio) {
                    radio.checked = true;
                }
                this.selectPrimaryOption(opt.option_id);
            });

            // Optional 5-point rating scale drawer (rendered under option)
            // Visible when primary option is selected on this question
            if (hasPrimary) {
                const ratingDrawer = document.createElement('div');
                ratingDrawer.className = 'mt-3.5 border-t border-slate-100 pt-3 ps-7';

                const ratingTitle = document.createElement('div');
                ratingTitle.className = 'mb-2 flex items-center justify-between text-xs text-slate-500';
                ratingTitle.innerHTML = `
                    <span class="font-medium text-slate-700">إلى أي مدى يشبهك هذا التصرف؟</span>
                    <span class="text-[11px] text-slate-400">تقييم إضافي اختياري</span>
                `;
                ratingDrawer.appendChild(ratingTitle);

                const scaleGrid = document.createElement('div');
                scaleGrid.className = 'flex flex-wrap gap-1.5 sm:gap-2';

                RATING_LEVELS.forEach((level) => {
                    const isSelectedRating = currentRating === level.value;
                    const ratingBtn = document.createElement('button');
                    ratingBtn.type = 'button';
                    ratingBtn.className = `rating-btn inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition-all ${
                        isSelectedRating
                            ? 'bg-brand-600 text-white shadow-xs'
                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                    }`;

                    ratingBtn.innerHTML = `
                        <span aria-hidden="true" class="text-sm">${level.emoji}</span>
                        <span>${level.label}</span>
                    `;

                    ratingBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.handleRatingClick(opt.option_id, level.value, isSelectedRating);
                    });

                    scaleGrid.appendChild(ratingBtn);
                });

                card.appendChild(ratingDrawer);
            }

            this.optionsContainer.appendChild(card);
        });

        this.updateNavButtons();
        this.renderNavGrid();
    }

    updateSpecialCardStyles() {
        const currentAnswer = this.getCurrentAnswer();

        this.noneFitCard.className = `relative flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-all ${
            currentAnswer.none_selected
                ? 'border-brand-600 bg-brand-50/50 ring-1 ring-brand-600'
                : 'border-slate-200 bg-white hover:bg-slate-50'
        }`;

        this.cannotJudgeCard.className = `relative flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-all ${
            currentAnswer.unable_to_judge
                ? 'border-brand-600 bg-brand-50/50 ring-1 ring-brand-600'
                : 'border-slate-200 bg-white hover:bg-slate-50'
        }`;
    }

    selectPrimaryOption(optionId) {
        const answer = this.getCurrentAnswer();
        answer.primary_option_id = optionId;
        answer.none_selected = false;
        answer.unable_to_judge = false;

        this.noneFitRadio.checked = false;
        this.cannotJudgeRadio.checked = false;

        this.renderCurrentQuestion();
        this.saveCurrentAnswer();
    }

    handleSpecialChoice(choiceType) {
        const answer = this.getCurrentAnswer();
        answer.primary_option_id = null;
        answer.ratings = {}; // Ratings are cleared for special exclusion choices

        if (choiceType === 'none_selected') {
            answer.none_selected = true;
            answer.unable_to_judge = false;
            this.cannotJudgeRadio.checked = false;
        } else {
            answer.unable_to_judge = true;
            answer.none_selected = false;
            this.noneFitRadio.checked = false;
        }

        this.renderCurrentQuestion();
        this.saveCurrentAnswer();
    }

    handleRatingClick(optionId, ratingValue, isAlreadySelected) {
        const answer = this.getCurrentAnswer();

        // Clicking the active rating again unrates (clears rating)
        if (isAlreadySelected) {
            delete answer.ratings[optionId];
            this.renderCurrentQuestion();
            this.saveCurrentAnswer();
            return;
        }

        // Conflict check: if student chose this option as Primary AND gives strong negative rating (-1 or -2)
        if (answer.primary_option_id === optionId && (ratingValue === -1 || ratingValue === -2)) {
            this.pendingConflict = { optionId, ratingValue };
            this.openConflictModal();
            return;
        }

        // Normal rating assignment
        answer.ratings[optionId] = ratingValue;
        this.renderCurrentQuestion();
        this.saveCurrentAnswer();
    }

    openConflictModal() {
        this.conflictModal.classList.remove('hidden');
    }

    closeConflictModal() {
        this.conflictModal.classList.add('hidden');
        this.pendingConflict = null;
    }

    resolveConflict(keepRating) {
        if (keepRating && this.pendingConflict) {
            const answer = this.getCurrentAnswer();
            answer.ratings[this.pendingConflict.optionId] = this.pendingConflict.ratingValue;
            this.renderCurrentQuestion();
            this.saveCurrentAnswer();
        }
        this.closeConflictModal();
    }

    async saveCurrentAnswer() {
        const question = this.getCurrentQuestion();
        const answer = this.getCurrentAnswer();
        if (!question || !answer) return;

        // Validate that one response state is selected
        const hasPrimary = answer.primary_option_id !== null;
        if (!hasPrimary && !answer.none_selected && !answer.unable_to_judge) {
            return; // Not answered yet
        }

        // Prepare payload according to contract H-03
        const ratingsArray = [];
        if (!answer.unable_to_judge && answer.ratings) {
            for (const [optId, ratingVal] of Object.entries(answer.ratings)) {
                ratingsArray.push({
                    option_id: Number(optId),
                    rating: Number(ratingVal),
                });
            }
        }

        const payload = {
            primary_option_id: answer.primary_option_id,
            none_selected: answer.none_selected,
            unable_to_judge: answer.unable_to_judge,
            ratings: ratingsArray,
        };

        this.setSaveStatus('saving', 'جارٍ الحفظ…');
        this.hideError();

        try {
            const url = `${this.saveBaseUrl}/${question.question_id}`;
            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                const errData = await response.json().catch(() => ({}));
                const errMsg = errData.message || 'تعذر حفظ الإجابة. حاول مرة أخرى.';
                throw new Error(errMsg);
            }

            const result = await response.json();
            this.setSaveStatus('saved', 'حُفظت الإجابة.');
            this.updateProgress();
            this.renderNavGrid();
            this.updateNavButtons();
        } catch (err) {
            this.setSaveStatus('error', 'تعذر حفظ الإجابة.');
            this.showError(err.message || 'تعذر حفظ الإجابة. حاول مرة أخرى.');
        }
    }

    setSaveStatus(status, message) {
        this.saveStatus = status;
        this.saveStatusText.textContent = message;

        if (status === 'saving') {
            this.saveStatusText.className = 'text-brand-600 font-semibold animate-pulse';
        } else if (status === 'saved') {
            this.saveStatusText.className = 'text-green-600 font-semibold';
        } else if (status === 'error') {
            this.saveStatusText.className = 'text-red-600 font-semibold';
        } else {
            this.saveStatusText.className = 'text-slate-400 font-medium';
        }
    }

    showError(message) {
        this.errorAlertMessage.textContent = message;
        this.errorAlert.classList.remove('hidden');
    }

    hideError() {
        this.errorAlert.classList.add('hidden');
    }

    updateProgress() {
        const total = this.questions.length || 18;
        let answeredCount = 0;

        this.questions.forEach((q) => {
            const ans = this.answers[q.question_id];
            if (ans && (ans.primary_option_id !== null || ans.none_selected || ans.unable_to_judge)) {
                answeredCount++;
            }
        });

        this.processedCountNum.textContent = answeredCount;
        const percentage = Math.round((answeredCount / total) * 100);
        this.progressBarFill.style.width = `${percentage}%`;
        this.progressBarContainer.setAttribute('aria-valuenow', percentage);
    }

    renderNavGrid() {
        this.questionsNavGrid.innerHTML = '';

        this.questions.forEach((q, idx) => {
            const ans = this.answers[q.question_id];
            const isAnswered = ans && (ans.primary_option_id !== null || ans.none_selected || ans.unable_to_judge);
            const isCurrent = idx === this.currentIndex;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('aria-label', `الموقف رقم ${q.position}`);
            btn.className = `flex h-9 w-full items-center justify-center rounded-lg text-xs font-bold transition-all ${
                isCurrent
                    ? 'bg-brand-600 text-white ring-2 ring-brand-600 ring-offset-2 shadow-xs'
                    : isAnswered
                    ? 'bg-green-100 text-green-900 border border-green-300 hover:bg-green-200'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            }`;

            btn.textContent = q.position;
            btn.addEventListener('click', () => this.goToIndex(idx));

            this.questionsNavGrid.appendChild(btn);
        });
    }

    updateNavButtons() {
        this.prevBtn.disabled = this.currentIndex <= 0;

        const isLastQuestion = this.currentIndex >= this.questions.length - 1;
        const allProcessed = this.checkAllProcessed();

        if (allProcessed) {
            this.completeBtn.classList.remove('hidden');
        } else {
            this.completeBtn.classList.add('hidden');
        }

        if (isLastQuestion) {
            this.nextBtn.classList.add('hidden');
        } else {
            this.nextBtn.classList.remove('hidden');
        }
    }

    checkAllProcessed() {
        if (this.questions.length === 0) return false;
        return this.questions.every((q) => {
            const ans = this.answers[q.question_id];
            return ans && (ans.primary_option_id !== null || ans.none_selected || ans.unable_to_judge);
        });
    }

    goToIndex(newIndex) {
        if (newIndex < 0 || newIndex >= this.questions.length) return;
        this.currentIndex = newIndex;
        this.renderCurrentQuestion();
    }

    goToNext() {
        if (this.currentIndex < this.questions.length - 1) {
            this.goToIndex(this.currentIndex + 1);
        }
    }

    goToPrevious() {
        if (this.currentIndex > 0) {
            this.goToIndex(this.currentIndex - 1);
        }
    }

    openCompletionModal() {
        this.completionModalError.classList.add('hidden');
        this.completionModal.classList.remove('hidden');
    }

    closeCompletionModal() {
        this.completionModal.classList.add('hidden');
    }

    async submitCompletion() {
        this.completionConfirmBtn.disabled = true;
        this.completionConfirmBtn.textContent = 'جارٍ الحفظ…';
        this.completionModalError.classList.add('hidden');

        try {
            const response = await fetch(this.completeUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
            });

            if (!response.ok) {
                const errData = await response.json().catch(() => ({}));
                const errMsg = errData.message || 'لم تكتمل المواقف المطلوبة بعد. راجع المواقف التي تحتاج معالجة، ثم حاول إكمال التقييم مرة أخرى.';
                this.completionModalError.textContent = errMsg;
                this.completionModalError.classList.remove('hidden');
                this.completionConfirmBtn.disabled = false;
                this.completionConfirmBtn.textContent = 'إكمال التقييم';
                return;
            }

            const data = await response.json();
            const resultUrl = data.data?.result_url || '/results';
            window.location.href = resultUrl;
        } catch (err) {
            this.completionModalError.textContent = 'تعذر الاتصال. تأكد من اتصالك بالإنترنت ثم حاول مرة أخرى.';
            this.completionModalError.classList.remove('hidden');
            this.completionConfirmBtn.disabled = false;
            this.completionConfirmBtn.textContent = 'إكمال التقييم';
        }
    }

    disableInputsForCompletedSession() {
        const inputs = this.app.querySelectorAll('input, button');
        inputs.forEach((el) => {
            if (el.id !== 'prev-btn' && el.id !== 'next-btn') {
                el.disabled = true;
            }
        });
        this.setSaveStatus('saved', 'اكتمل هذا التقييم، ولا يمكن تعديل إجاباته.');
    }
}

// Auto-initialize when #assessment-app is mounted
document.addEventListener('DOMContentLoaded', () => {
    const appEl = document.getElementById('assessment-app');
    if (appEl) {
        new AssessmentJourney(appEl);
    }
});

export default AssessmentJourney;
