<?php

namespace Database\Seeders;

use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the approved v1.2 assessment question bank.
 *
 * Source of truth: docs/04-assessment/question_bank_specification.md
 * (18 scenarios x 4 options = 72 options, 12 occurrences per RIASEC code).
 *
 * NOTE: the `version_number` column is an unsigned integer, so the semantic
 * version "1.2" is stored as 12.
 */
class AssessmentQuestionBankSeeder extends Seeder
{
    /**
     * Expected counts, used for the built-in self-validation.
     */
    private const QUESTIONS_COUNT = 18;

    private const OPTIONS_PER_QUESTION = 4;

    private const RIASEC_CODES = ['R', 'I', 'A', 'S', 'E', 'C'];

    private const OPTIONS_PER_CODE = 12;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questionsData = $this->questionsData();

        DB::transaction(function () use ($questionsData): void {
            // Semantic version "1.2" is stored as the integer 12.
            $version = AssessmentVersion::updateOrCreate(
                ['version_number' => 12],
                [
                    'status' => 'active',
                    'published_at' => now(),
                ]
            );

            foreach ($questionsData as $position => $questionData) {
                $question = Question::updateOrCreate(
                    [
                        'assessment_version_id' => $version->id,
                        'position' => $position,
                    ],
                    [
                        'scenario' => $questionData['scenario'],
                    ]
                );

                foreach ($questionData['options'] as $optionPosition => $optionData) {
                    QuestionOption::updateOrCreate(
                        [
                            'question_id' => $question->id,
                            'position' => $optionPosition,
                        ],
                        [
                            'option_text' => $optionData['text'],
                            'riasec_code' => $optionData['code'],
                        ]
                    );
                }
            }

            $this->validate($version);
        });
    }

    /**
     * Verify the seeded bank matches the approved specification.
     *
     * Runs inside the transaction, so a failed check rolls everything back.
     *
     * @throws \RuntimeException
     */
    private function validate(AssessmentVersion $version): void
    {
        $questions = $version->questions()
            ->orderBy('position')
            ->get();

        if ($questions->count() !== self::QUESTIONS_COUNT) {
            throw new \RuntimeException(sprintf(
                'Expected %d questions, got %d.',
                self::QUESTIONS_COUNT,
                $questions->count()
            ));
        }

        // Detect duplicated question positions within this version.
        $questionPositions = $questions->pluck('position')->all();
        if (count($questionPositions) !== count(array_unique($questionPositions))) {
            throw new \RuntimeException('Duplicate question positions found within the version.');
        }

        $totalOptions = 0;
        $codeCounts = array_fill_keys(self::RIASEC_CODES, 0);

        foreach ($questions as $question) {
            $options = $question->questionOptions()
                ->orderBy('position')
                ->get();

            if ($options->count() !== self::OPTIONS_PER_QUESTION) {
                throw new \RuntimeException(sprintf(
                    'Question at position %d: expected %d options, got %d.',
                    $question->position,
                    self::OPTIONS_PER_QUESTION,
                    $options->count()
                ));
            }

            // Detect duplicated option positions within a question.
            $optionPositions = $options->pluck('position')->all();
            if (count($optionPositions) !== count(array_unique($optionPositions))) {
                throw new \RuntimeException(sprintf(
                    'Duplicate option positions in question at position %d.',
                    $question->position
                ));
            }

            foreach ($options as $option) {
                $codeCounts[$option->riasec_code]++;
                $totalOptions++;
            }
        }

        if ($totalOptions !== self::QUESTIONS_COUNT * self::OPTIONS_PER_QUESTION) {
            throw new \RuntimeException(sprintf(
                'Expected %d options, got %d.',
                self::QUESTIONS_COUNT * self::OPTIONS_PER_QUESTION,
                $totalOptions
            ));
        }

        foreach ($codeCounts as $code => $count) {
            if ($count !== self::OPTIONS_PER_CODE) {
                throw new \RuntimeException(sprintf(
                    'RIASEC code %s: expected %d occurrences, got %d.',
                    $code,
                    self::OPTIONS_PER_CODE,
                    $count
                ));
            }
        }
    }

    /**
     * The approved question bank, transcribed verbatim from
     * docs/04-assessment/question_bank_specification.md (spec v1.2).
     *
     * Option order follows the specification. Presentation-time shuffling is a
     * runtime concern and is intentionally NOT applied here.
     *
     * @return array<int, array{scenario: string, options: array<int, array{code: string, text: string}>}>
     */
    private function questionsData(): array
    {
        return [
            1 => [
                'scenario' => 'تستعد الأسرة أو مجموعة من الجيران لمناسبة بسيطة، ويحتاج المكان إلى بعض التجهيز. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أجهز الأشياء المطلوبة وأساعد في ترتيبها عمليًا.'],
                    2 => ['code' => 'I', 'text' => 'أراجع الاحتياجات وأفكر فيما قد ينقص أو يسبب مشكلة.'],
                    3 => ['code' => 'A', 'text' => 'أهتم بجمال المكان وتناسق الألوان والترتيب.'],
                    4 => ['code' => 'C', 'text' => 'أرتب الأعمال بين المشاركين وأتابع ما تم إنجازه.'],
                ],
            ],
            2 => [
                'scenario' => 'خُصص مكان في البيت لحفظ الكتب والأغراض، لكن بعضها ما زال يضيع أو يتلف. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أفحص المكان وأجرب ترتيبًا عمليًا أفضل.'],
                    2 => ['code' => 'I', 'text' => 'أبحث عن سبب المشكلة، مثل الرطوبة أو الازدحام أو كثرة الاستخدام.'],
                    3 => ['code' => 'A', 'text' => 'أبتكر شكلًا جديدًا يجعل المكان أوضح وأجمل.'],
                    4 => ['code' => 'C', 'text' => 'أصنف الأغراض وأحدد مكانًا ثابتًا لكل نوع.'],
                ],
            ],
            3 => [
                'scenario' => 'تأخر وصول الماء إلى البيت أو الحي، وأصبح الموجود قليلًا. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أتأكد من الأوعية والصنابير وأرتب استخدام الماء المتاح عمليًا.'],
                    2 => ['code' => 'I', 'text' => 'أحاول معرفة سبب التأخر وهل المشكلة عامة أم في مكان محدد.'],
                    3 => ['code' => 'S', 'text' => 'أساعد أفراد الأسرة أو الجيران الأكثر حاجة إلى تدبير المتاح.'],
                    4 => ['code' => 'E', 'text' => 'أتواصل مع المعنيين أو أنسق مع الآخرين لمعرفة حل مشترك.'],
                ],
            ],
            4 => [
                'scenario' => 'سألك طفل أصغر عن كيفية عمل شيء بسيط في البيت أو سبب حدوث أمر يراه يوميًا. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أوضح الفكرة باستخدام أشياء آمنة ومتاحة يمكنه رؤيتها.'],
                    2 => ['code' => 'A', 'text' => 'أرسم صورة أو أبتكر مثالًا يجذب انتباهه.'],
                    3 => ['code' => 'E', 'text' => 'أشجعه على المحاولة وأجعله متحمسًا لاكتشاف الإجابة.'],
                    4 => ['code' => 'C', 'text' => 'أقسم الشرح إلى خطوات قصيرة وأرتبها بوضوح.'],
                ],
            ],
            5 => [
                'scenario' => 'لاحظت بين الأصدقاء أو في الحي مشكلة متكررة، مثل رمي المخلفات في مكان غير مناسب. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'I', 'text' => 'أبحث عن سبب تكرار المشكلة وما الذي يزيدها أو يقللها.'],
                    2 => ['code' => 'A', 'text' => 'أصنع عبارة أو رسمًا بسيطًا يلفت الانتباه إلى الحل.'],
                    3 => ['code' => 'S', 'text' => 'أتحدث مع الناس بهدوء وأستمع إلى ما يصعب عليهم.'],
                    4 => ['code' => 'E', 'text' => 'أدعو مجموعة صغيرة إلى خطوة مشتركة وأشجعها على البدء.'],
                ],
            ],
            6 => [
                'scenario' => 'انتهى شرح أحد الدروس، لكن بقيت فكرة لم تفهمها بوضوح. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أطبقها على مثال بيدي حتى أرى كيف تعمل.'],
                    2 => ['code' => 'I', 'text' => 'أراجع الأمثلة وأحاول اكتشاف الجزء الذي سبب لي الصعوبة.'],
                    3 => ['code' => 'A', 'text' => 'أرسم الفكرة أو أربطها بصورة تساعدني على فهمها.'],
                    4 => ['code' => 'S', 'text' => 'أطلب من المعلم أو أحد زملائي شرحها، ثم أناقشها معه.'],
                ],
            ],
            7 => [
                'scenario' => 'اتفقت مع عدد من زملائك على المراجعة، لكن الوقت بدأ يضيع دون إنجاز واضح. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'I', 'text' => 'أبحث عن سبب ضياع الوقت وأقترح طريقة يمكن اختبارها.'],
                    2 => ['code' => 'S', 'text' => 'أساعد من لم يفهم وأشجعه على المشاركة والسؤال.'],
                    3 => ['code' => 'E', 'text' => 'أبدأ النقاش وأشجع المجموعة على الالتزام بالمراجعة.'],
                    4 => ['code' => 'C', 'text' => 'أقسم الوقت والموضوعات وأتابع ما تم إنجازه.'],
                ],
            ],
            8 => [
                'scenario' => 'قبل الاختبار، وجدت أن دفاترك وأوراق المراجعة غير مرتبة وبعض المعلومات يصعب العثور عليها. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أفحص الدفاتر والأوراق وأتأكد من اكتمالها.'],
                    2 => ['code' => 'I', 'text' => 'أراجع المحتوى لأميز المهم من المكرر أو غير الواضح.'],
                    3 => ['code' => 'S', 'text' => 'أطلب من زملائي ما ينقصني وأساعدهم بما ينقصهم.'],
                    4 => ['code' => 'C', 'text' => 'أصنف الأوراق حسب المادة والموضوع وأرتبها بوضوح.'],
                ],
            ],
            9 => [
                'scenario' => 'لاحظت أنت وعدد من زملائك انخفاض درجاتكم في إحدى المواد. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'I', 'text' => 'أحلل الأخطاء المتكررة لأعرف الجزء الذي سبب المشكلة.'],
                    2 => ['code' => 'A', 'text' => 'أبتكر أمثلة أو رسومات تساعد على تذكر الأفكار.'],
                    3 => ['code' => 'S', 'text' => 'أراجع مع الزملاء الذين يحتاجون إلى المساعدة.'],
                    4 => ['code' => 'C', 'text' => 'أرتب الموضوعات في خطة مراجعة واضحة.'],
                ],
            ],
            10 => [
                'scenario' => 'احتاج الفصل إلى إعادة ترتيب المقاعد والكتب قبل بدء الدراسة. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أشارك في نقل الأشياء وترتيبها عمليًا.'],
                    2 => ['code' => 'A', 'text' => 'أهتم بأن يصبح شكل الفصل مرتبًا ومريحًا.'],
                    3 => ['code' => 'S', 'text' => 'أراعي احتياجات الزملاء وأسألهم عن الترتيب الأنسب.'],
                    4 => ['code' => 'E', 'text' => 'أنظم المشاركة وأشجع الجميع على المساعدة.'],
                ],
            ],
            11 => [
                'scenario' => 'لديك عدة ساعات فارغة، ولا يوجد عمل مطلوب منك. أي نشاط تختاره غالبًا؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أصلح شيئًا بسيطًا أو أصنع شيئًا بيدي.'],
                    2 => ['code' => 'A', 'text' => 'أرسم أو أصمم أو أكتب شيئًا من خيالي.'],
                    3 => ['code' => 'E', 'text' => 'أبدأ نشاطًا مشتركًا وأشجع أصدقائي على المشاركة فيه.'],
                    4 => ['code' => 'C', 'text' => 'أرتب كتبي أو أغراضي أو مجموعة أحتفظ بها.'],
                ],
            ],
            12 => [
                'scenario' => 'تريد شراء غرض تحتاج إليه، وأمامك عدة خيارات متقاربة. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أفحص الغرض بنفسي وأتأكد من جودته وصلاحيته.'],
                    2 => ['code' => 'I', 'text' => 'أقارن الخيارات وأبحث عن سبب اختلاف الجودة والسعر.'],
                    3 => ['code' => 'E', 'text' => 'أتحدث مع البائع وأحاول الوصول إلى الخيار أو السعر الأنسب.'],
                    4 => ['code' => 'C', 'text' => 'أحدد ميزانيتي وأسجل المواصفات وأرتب الخيارات قبل الاختيار.'],
                ],
            ],
            13 => [
                'scenario' => 'اختلف عدد من أصدقائك حول أمر يخصهم، وبدأ الخلاف يؤثر في علاقتهم. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'I', 'text' => 'أفهم سبب الخلاف وأميز بين الحقائق وسوء الفهم.'],
                    2 => ['code' => 'S', 'text' => 'أستمع إلى كل طرف وأساعدهما على فهم بعضهما.'],
                    3 => ['code' => 'E', 'text' => 'أبادر بجمعهم وأدفعهم إلى الاتفاق على حل.'],
                    4 => ['code' => 'C', 'text' => 'أحدد نقاط الاتفاق والخلاف وأرتب الحلول الممكنة.'],
                ],
            ],
            14 => [
                'scenario' => 'وجدت مساحة صغيرة غير مستخدمة يمكن تحويلها إلى مكان مفيد. أي مساهمة تشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أنظف المكان وأشارك في تجهيزه عمليًا.'],
                    2 => ['code' => 'A', 'text' => 'أتصور شكلًا جديدًا للمكان وأضيف إليه لمسة جميلة.'],
                    3 => ['code' => 'S', 'text' => 'أسأل من سيستخدمونه عما يحتاجونه وأراعي راحتهم.'],
                    4 => ['code' => 'E', 'text' => 'أقنع الآخرين بالفكرة وأنظم مشاركتهم في تنفيذها.'],
                ],
            ],
            15 => [
                'scenario' => 'مررت بتجربة مهمة أو شاهدت موقفًا بقي في ذاكرتك. كيف تميل إلى التعبير عنه؟',
                'options' => [
                    1 => ['code' => 'A', 'text' => 'أحوله إلى رسم أو قصة أو فكرة مبتكرة.'],
                    2 => ['code' => 'S', 'text' => 'أتحدث عنه مع شخص قريب وأستمع إلى تجربته أيضًا.'],
                    3 => ['code' => 'E', 'text' => 'أرويه أمام الآخرين بطريقة مؤثرة تلفت انتباههم.'],
                    4 => ['code' => 'C', 'text' => 'أكتب تفاصيله وأرتب أحداثه حتى أحفظها بوضوح.'],
                ],
            ],
            16 => [
                'scenario' => 'لاحظت أن نبتة كانت تنمو جيدًا بدأت تذبل، مع أن شخصًا ما زال يسقيها. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أفحص التربة ومكان النبتة وكمية الماء، وأجرب تعديل عامل واحد.'],
                    2 => ['code' => 'I', 'text' => 'أقارن حالتها السابقة والحالية لأحدد السبب الأكثر احتمالًا.'],
                    3 => ['code' => 'A', 'text' => 'أرسم تغير النبتة أو أوضح مراحل حالتها بصريًا.'],
                    4 => ['code' => 'S', 'text' => 'أشرح لمن يعتني بها ما لاحظته وأساعده على متابعة حالتها.'],
                ],
            ],
            17 => [
                'scenario' => 'تتكرر مشكلة بسيطة في البيت، لكنها لا تحدث في كل مرة، مثل تسرب الماء من وعاء أو تعطل مصباح أحيانًا. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أفحص الأجزاء الظاهرة وأجرب خطوة آمنة ومباشرة.'],
                    2 => ['code' => 'I', 'text' => 'أقارن الظروف التي تظهر فيها المشكلة بالظروف التي لا تظهر فيها.'],
                    3 => ['code' => 'E', 'text' => 'أوضح ما توصلت إليه للأسرة وأقترح تنفيذ الحل المناسب.'],
                    4 => ['code' => 'C', 'text' => 'أسجل وقت حدوث المشكلة والظروف والمحاولات والنتائج.'],
                ],
            ],
            18 => [
                'scenario' => 'سمعت معلومة عن الصحة أو الدراسة يكررها كثيرون، ثم وجدت أنها ناقصة أو غير دقيقة. كيف تفضل توضيحها؟',
                'options' => [
                    1 => ['code' => 'A', 'text' => 'أصنع مثالًا أو رسمًا بسيطًا يوضح الفرق بين المعلومة الصحيحة والخاطئة.'],
                    2 => ['code' => 'S', 'text' => 'أناقشها بهدوء مع الآخرين وأراعي سبب اقتناعهم بها.'],
                    3 => ['code' => 'E', 'text' => 'أبادر بتوضيح التصحيح للمجموعة بطريقة مقنعة.'],
                    4 => ['code' => 'C', 'text' => 'أجمع النقاط الصحيحة وأرتبها بوضوح قبل مشاركتها.'],
                ],
            ],
        ];
    }
}
