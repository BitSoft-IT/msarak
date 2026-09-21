<?php

namespace Tests\Feature;

use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\QuestionOption;
use Database\Seeders\AssessmentQuestionBankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Verifies that AssessmentQuestionBankSeeder installs the approved v1.2 bank.
 *
 * Expected shape: 1 active version (1.2 stored as 12), 18 questions,
 * 72 options (4 per question), 12 occurrences of each RIASEC code.
 *
 * Scenarios, option texts and codes below are transcribed verbatim from
 * docs/04-assessment/question_bank_specification.md (spec v1.2).
 */
class AssessmentQuestionBankTest extends TestCase
{
    use RefreshDatabase;

    private const VERSION_NUMBER = 12;

    private const APPROVED_CODES = ['R', 'I', 'A', 'S', 'E', 'C'];

    private const FIFTH_OPTION_TEXT = 'لا يشبهني أي من هذه التصرفات';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AssessmentQuestionBankSeeder::class);
    }

    public function test_assessment_version_1_2_is_created_as_active(): void
    {
        $version = AssessmentVersion::where('version_number', self::VERSION_NUMBER)->first();

        $this->assertNotNull($version, 'Assessment version 1.2 (stored as 12) was not created.');
        $this->assertSame('active', $version->status);
        $this->assertNotNull($version->published_at);
    }

    public function test_seeds_exactly_eighteen_questions(): void
    {
        $this->assertSame(18, Question::count(), 'The bank must contain exactly 18 questions.');
    }

    public function test_seeds_exactly_seventy_two_options(): void
    {
        $this->assertSame(72, QuestionOption::count(), 'The bank must contain exactly 72 options.');
    }

    public function test_every_question_has_exactly_four_options(): void
    {
        $questions = Question::with('questionOptions')->orderBy('position')->get();

        $questions->each(function (Question $question): void {
            $this->assertCount(
                4,
                $question->questionOptions,
                "Question at position {$question->position} must have exactly 4 options."
            );
        });
    }

    public function test_riasec_codes_are_within_the_approved_set(): void
    {
        $codes = QuestionOption::pluck('riasec_code')->unique()->all();

        sort($codes);
        $expected = self::APPROVED_CODES;
        sort($expected);

        $this->assertSame($expected, $codes, 'Only R, I, A, S, E, C codes are allowed.');
    }

    public function test_riasec_distribution_is_balanced_at_twelve_each(): void
    {
        $counts = QuestionOption::query()
            ->selectRaw('riasec_code, COUNT(*) as total')
            ->groupBy('riasec_code')
            ->pluck('total', 'riasec_code');

        foreach (self::APPROVED_CODES as $code) {
            $this->assertSame(
                12,
                (int) $counts->get($code, 0),
                "RIASEC code {$code} must appear exactly 12 times."
            );
        }
    }

    public function test_question_positions_are_not_duplicated_within_the_version(): void
    {
        $positions = Question::orderBy('position')->pluck('position')->all();

        $this->assertSame($positions, array_unique($positions), 'Question positions must not repeat.');
        $this->assertSame(range(1, 18), $positions, 'Question positions must be a contiguous 1..18 range.');
    }

    public function test_option_positions_are_not_duplicated_within_each_question(): void
    {
        Question::with('questionOptions')->orderBy('position')->get()
            ->each(function (Question $question): void {
                $positions = $question->questionOptions->pluck('position')->all();

                $this->assertSame(
                    $positions,
                    array_unique($positions),
                    "Option positions must not repeat in question {$question->position}."
                );
                $this->assertSame(
                    range(1, 4),
                    $positions,
                    "Option positions must be a contiguous 1..4 range in question {$question->position}."
                );
            });
    }

    public function test_no_fifth_option_is_stored_in_question_options(): void
    {
        $exists = QuestionOption::query()
            ->where('option_text', 'like', '%'.self::FIFTH_OPTION_TEXT.'%')
            ->exists();

        $this->assertFalse(
            $exists,
            'The fifth option must not be stored as a question_option row.'
        );

        $this->assertSame(0, AssessmentVersion::first()->questions()->get()->flatMap->questionOptions->count() % 4);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(AssessmentQuestionBankSeeder::class);
        $this->seed(AssessmentQuestionBankSeeder::class);

        $this->assertSame(1, AssessmentVersion::where('version_number', self::VERSION_NUMBER)->count());
        $this->assertSame(18, Question::count());
        $this->assertSame(72, QuestionOption::count());
    }

    /**
     * Full bank, verbatim from the spec. Each entry is a positional argument
     * list: [$position, $scenario, $options] where $options is a list of
     * ['code' => ..., 'text' => ...] in presentation order.
     */
    public static function bankProvider(): array
    {
        return [
            'Q01' => [
                1,
                'تستعد الأسرة أو مجموعة من الجيران لمناسبة بسيطة، ويحتاج المكان إلى بعض التجهيز. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أجهز الأشياء المطلوبة وأساعد في ترتيبها عمليًا.'],
                    ['code' => 'I', 'text' => 'أراجع الاحتياجات وأفكر فيما قد ينقص أو يسبب مشكلة.'],
                    ['code' => 'A', 'text' => 'أهتم بجمال المكان وتناسق الألوان والترتيب.'],
                    ['code' => 'C', 'text' => 'أرتب الأعمال بين المشاركين وأتابع ما تم إنجازه.'],
                ],
            ],
            'Q02' => [
                2,
                'خُصص مكان في البيت لحفظ الكتب والأغراض، لكن بعضها ما زال يضيع أو يتلف. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أفحص المكان وأجرب ترتيبًا عمليًا أفضل.'],
                    ['code' => 'I', 'text' => 'أبحث عن سبب المشكلة، مثل الرطوبة أو الازدحام أو كثرة الاستخدام.'],
                    ['code' => 'A', 'text' => 'أبتكر شكلًا جديدًا يجعل المكان أوضح وأجمل.'],
                    ['code' => 'C', 'text' => 'أصنف الأغراض وأحدد مكانًا ثابتًا لكل نوع.'],
                ],
            ],
            'Q03' => [
                3,
                'تأخر وصول الماء إلى البيت أو الحي، وأصبح الموجود قليلًا. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أتأكد من الأوعية والصنابير وأرتب استخدام الماء المتاح عمليًا.'],
                    ['code' => 'I', 'text' => 'أحاول معرفة سبب التأخر وهل المشكلة عامة أم في مكان محدد.'],
                    ['code' => 'S', 'text' => 'أساعد أفراد الأسرة أو الجيران الأكثر حاجة إلى تدبير المتاح.'],
                    ['code' => 'E', 'text' => 'أتواصل مع المعنيين أو أنسق مع الآخرين لمعرفة حل مشترك.'],
                ],
            ],
            'Q04' => [
                4,
                'سألك طفل أصغر عن كيفية عمل شيء بسيط في البيت أو سبب حدوث أمر يراه يوميًا. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أوضح الفكرة باستخدام أشياء آمنة ومتاحة يمكنه رؤيتها.'],
                    ['code' => 'A', 'text' => 'أرسم صورة أو أبتكر مثالًا يجذب انتباهه.'],
                    ['code' => 'E', 'text' => 'أشجعه على المحاولة وأجعله متحمسًا لاكتشاف الإجابة.'],
                    ['code' => 'C', 'text' => 'أقسم الشرح إلى خطوات قصيرة وأرتبها بوضوح.'],
                ],
            ],
            'Q05' => [
                5,
                'لاحظت بين الأصدقاء أو في الحي مشكلة متكررة، مثل رمي المخلفات في مكان غير مناسب. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'I', 'text' => 'أبحث عن سبب تكرار المشكلة وما الذي يزيدها أو يقللها.'],
                    ['code' => 'A', 'text' => 'أصنع عبارة أو رسمًا بسيطًا يلفت الانتباه إلى الحل.'],
                    ['code' => 'S', 'text' => 'أتحدث مع الناس بهدوء وأستمع إلى ما يصعب عليهم.'],
                    ['code' => 'E', 'text' => 'أدعو مجموعة صغيرة إلى خطوة مشتركة وأشجعها على البدء.'],
                ],
            ],
            'Q06' => [
                6,
                'انتهى شرح أحد الدروس، لكن بقيت فكرة لم تفهمها بوضوح. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أطبقها على مثال بيدي حتى أرى كيف تعمل.'],
                    ['code' => 'I', 'text' => 'أراجع الأمثلة وأحاول اكتشاف الجزء الذي سبب لي الصعوبة.'],
                    ['code' => 'A', 'text' => 'أرسم الفكرة أو أربطها بصورة تساعدني على فهمها.'],
                    ['code' => 'S', 'text' => 'أطلب من المعلم أو أحد زملائي شرحها، ثم أناقشها معه.'],
                ],
            ],
            'Q07' => [
                7,
                'اتفقت مع عدد من زملائك على المراجعة، لكن الوقت بدأ يضيع دون إنجاز واضح. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'I', 'text' => 'أبحث عن سبب ضياع الوقت وأقترح طريقة يمكن اختبارها.'],
                    ['code' => 'S', 'text' => 'أساعد من لم يفهم وأشجعه على المشاركة والسؤال.'],
                    ['code' => 'E', 'text' => 'أبدأ النقاش وأشجع المجموعة على الالتزام بالمراجعة.'],
                    ['code' => 'C', 'text' => 'أقسم الوقت والموضوعات وأتابع ما تم إنجازه.'],
                ],
            ],
            'Q08' => [
                8,
                'قبل الاختبار، وجدت أن دفاترك وأوراق المراجعة غير مرتبة وبعض المعلومات يصعب العثور عليها. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أفحص الدفاتر والأوراق وأتأكد من اكتمالها.'],
                    ['code' => 'I', 'text' => 'أراجع المحتوى لأميز المهم من المكرر أو غير الواضح.'],
                    ['code' => 'S', 'text' => 'أطلب من زملائي ما ينقصني وأساعدهم بما ينقصهم.'],
                    ['code' => 'C', 'text' => 'أصنف الأوراق حسب المادة والموضوع وأرتبها بوضوح.'],
                ],
            ],
            'Q09' => [
                9,
                'لاحظت أنت وعدد من زملائك انخفاض درجاتكم في إحدى المواد. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'I', 'text' => 'أحلل الأخطاء المتكررة لأعرف الجزء الذي سبب المشكلة.'],
                    ['code' => 'A', 'text' => 'أبتكر أمثلة أو رسومات تساعد على تذكر الأفكار.'],
                    ['code' => 'S', 'text' => 'أراجع مع الزملاء الذين يحتاجون إلى المساعدة.'],
                    ['code' => 'C', 'text' => 'أرتب الموضوعات في خطة مراجعة واضحة.'],
                ],
            ],
            'Q10' => [
                10,
                'احتاج الفصل إلى إعادة ترتيب المقاعد والكتب قبل بدء الدراسة. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أشارك في نقل الأشياء وترتيبها عمليًا.'],
                    ['code' => 'A', 'text' => 'أهتم بأن يصبح شكل الفصل مرتبًا ومريحًا.'],
                    ['code' => 'S', 'text' => 'أراعي احتياجات الزملاء وأسألهم عن الترتيب الأنسب.'],
                    ['code' => 'E', 'text' => 'أنظم المشاركة وأشجع الجميع على المساعدة.'],
                ],
            ],
            'Q11' => [
                11,
                'لديك عدة ساعات فارغة، ولا يوجد عمل مطلوب منك. أي نشاط تختاره غالبًا؟',
                [
                    ['code' => 'R', 'text' => 'أصلح شيئًا بسيطًا أو أصنع شيئًا بيدي.'],
                    ['code' => 'A', 'text' => 'أرسم أو أصمم أو أكتب شيئًا من خيالي.'],
                    ['code' => 'E', 'text' => 'أبدأ نشاطًا مشتركًا وأشجع أصدقائي على المشاركة فيه.'],
                    ['code' => 'C', 'text' => 'أرتب كتبي أو أغراضي أو مجموعة أحتفظ بها.'],
                ],
            ],
            'Q12' => [
                12,
                'تريد شراء غرض تحتاج إليه، وأمامك عدة خيارات متقاربة. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أفحص الغرض بنفسي وأتأكد من جودته وصلاحيته.'],
                    ['code' => 'I', 'text' => 'أقارن الخيارات وأبحث عن سبب اختلاف الجودة والسعر.'],
                    ['code' => 'E', 'text' => 'أتحدث مع البائع وأحاول الوصول إلى الخيار أو السعر الأنسب.'],
                    ['code' => 'C', 'text' => 'أحدد ميزانيتي وأسجل المواصفات وأرتب الخيارات قبل الاختيار.'],
                ],
            ],
            'Q13' => [
                13,
                'اختلف عدد من أصدقائك حول أمر يخصهم، وبدأ الخلاف يؤثر في علاقتهم. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'I', 'text' => 'أفهم سبب الخلاف وأميز بين الحقائق وسوء الفهم.'],
                    ['code' => 'S', 'text' => 'أستمع إلى كل طرف وأساعدهما على فهم بعضهما.'],
                    ['code' => 'E', 'text' => 'أبادر بجمعهم وأدفعهم إلى الاتفاق على حل.'],
                    ['code' => 'C', 'text' => 'أحدد نقاط الاتفاق والخلاف وأرتب الحلول الممكنة.'],
                ],
            ],
            'Q14' => [
                14,
                'وجدت مساحة صغيرة غير مستخدمة يمكن تحويلها إلى مكان مفيد. أي مساهمة تشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أنظف المكان وأشارك في تجهيزه عمليًا.'],
                    ['code' => 'A', 'text' => 'أتصور شكلًا جديدًا للمكان وأضيف إليه لمسة جميلة.'],
                    ['code' => 'S', 'text' => 'أسأل من سيستخدمونه عما يحتاجونه وأراعي راحتهم.'],
                    ['code' => 'E', 'text' => 'أقنع الآخرين بالفكرة وأنظم مشاركتهم في تنفيذها.'],
                ],
            ],
            'Q15' => [
                15,
                'مررت بتجربة مهمة أو شاهدت موقفًا بقي في ذاكرتك. كيف تميل إلى التعبير عنه؟',
                [
                    ['code' => 'A', 'text' => 'أحوله إلى رسم أو قصة أو فكرة مبتكرة.'],
                    ['code' => 'S', 'text' => 'أتحدث عنه مع شخص قريب وأستمع إلى تجربته أيضًا.'],
                    ['code' => 'E', 'text' => 'أرويه أمام الآخرين بطريقة مؤثرة تلفت انتباههم.'],
                    ['code' => 'C', 'text' => 'أكتب تفاصيله وأرتب أحداثه حتى أحفظها بوضوح.'],
                ],
            ],
            'Q16' => [
                16,
                'لاحظت أن نبتة كانت تنمو جيدًا بدأت تذبل، مع أن شخصًا ما زال يسقيها. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أفحص التربة ومكان النبتة وكمية الماء، وأجرب تعديل عامل واحد.'],
                    ['code' => 'I', 'text' => 'أقارن حالتها السابقة والحالية لأحدد السبب الأكثر احتمالًا.'],
                    ['code' => 'A', 'text' => 'أرسم تغير النبتة أو أوضح مراحل حالتها بصريًا.'],
                    ['code' => 'S', 'text' => 'أشرح لمن يعتني بها ما لاحظته وأساعده على متابعة حالتها.'],
                ],
            ],
            'Q17' => [
                17,
                'تتكرر مشكلة بسيطة في البيت، لكنها لا تحدث في كل مرة، مثل تسرب الماء من وعاء أو تعطل مصباح أحيانًا. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أفحص الأجزاء الظاهرة وأجرب خطوة آمنة ومباشرة.'],
                    ['code' => 'I', 'text' => 'أقارن الظروف التي تظهر فيها المشكلة بالظروف التي لا تظهر فيها.'],
                    ['code' => 'E', 'text' => 'أوضح ما توصلت إليه للأسرة وأقترح تنفيذ الحل المناسب.'],
                    ['code' => 'C', 'text' => 'أسجل وقت حدوث المشكلة والظروف والمحاولات والنتائج.'],
                ],
            ],
            'Q18' => [
                18,
                'سمعت معلومة عن الصحة أو الدراسة يكررها كثيرون، ثم وجدت أنها ناقصة أو غير دقيقة. كيف تفضل توضيحها؟',
                [
                    ['code' => 'A', 'text' => 'أصنع مثالًا أو رسمًا بسيطًا يوضح الفرق بين المعلومة الصحيحة والخاطئة.'],
                    ['code' => 'S', 'text' => 'أناقشها بهدوء مع الآخرين وأراعي سبب اقتناعهم بها.'],
                    ['code' => 'E', 'text' => 'أبادر بتوضيح التصحيح للمجموعة بطريقة مقنعة.'],
                    ['code' => 'C', 'text' => 'أجمع النقاط الصحيحة وأرتبها بوضوح قبل مشاركتها.'],
                ],
            ],
        ];
    }

    #[DataProvider('bankProvider')]
    public function test_seeded_bank_matches_the_specification_verbatim(int $position, string $scenario, array $options): void
    {
        $question = Question::where('position', $position)->first();

        $this->assertNotNull($question, "Question at position {$position} is missing.");
        $this->assertSame($scenario, $question->scenario, "Scenario at position {$position} does not match the spec.");

        $seeded = $question->questionOptions()
            ->orderBy('position')
            ->get()
            ->map(fn (QuestionOption $option): array => [
                'code' => $option->riasec_code,
                'text' => $option->option_text,
            ])
            ->all();

        $this->assertSame($options, $seeded, "Options at position {$position} do not match the spec.");
    }
}
