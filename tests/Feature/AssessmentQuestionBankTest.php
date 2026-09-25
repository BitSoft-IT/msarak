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
                'تستعد الأسرة أو بعض الجيران لمناسبة بسيطة، ومكان المناسبة يحتاج شوية تجهيز. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أجهز الأشياء المطلوبة وأساعد في ترتيبها بشكل عملي.'],
                    ['code' => 'I', 'text' => 'أراجع الاحتياجات وأفكر إيش ممكن ينقص أو يسبب مشكلة.'],
                    ['code' => 'A', 'text' => 'أهتم بشكل المكان وتناسق الألوان والترتيب.'],
                    ['code' => 'C', 'text' => 'أرتب الشغل بين الموجودين وأتابع إيش قد انعمل.'],
                ],
            ],
    
            'Q02' => [
                2,
                'خصصوا مكان في البيت لحفظ الكتب والأغراض، لكن بعضها ما يزال يضيع أو يتلف. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أفحص المكان وأجرب ترتيب عملي أفضل.'],
                    ['code' => 'I', 'text' => 'أبحث عن سبب المشكلة، مثل الرطوبة أو الزحمة أو كثرة الاستخدام.'],
                    ['code' => 'A', 'text' => 'أبتكر شكل جديد يخلي المكان أوضح وأجمل.'],
                    ['code' => 'C', 'text' => 'أصنف الأغراض وأحدد مكان ثابت لكل نوع.'],
                ],
            ],
    
            'Q03' => [
                3,
                'تأخر وصول الماء للبيت أو الحارة، والموجود صار قليل. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أتأكد من المياه المتبقية وأرتب استخدام الماء الموجود بشكل عملي.'],
                    ['code' => 'I', 'text' => 'أحاول أعرف سبب التأخر وهل المشكلة عامة أو في مكان معين.'],
                    ['code' => 'S', 'text' => 'أساعد أفراد الأسرة أو الجيران الأكثر حاجة في تدبير الموجود.'],
                    ['code' => 'E', 'text' => 'أتواصل مع المعنيين أو أنسق مع غيري عشان نعرف حل مشترك.'],
                ],
            ],
    
            'Q04' => [
                4,
                'سألك طفل أصغر منك عن كيف يشتغل شيء بسيط في البيت أو عن سبب حصول شيء يشوفه كل يوم. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أوضح له الفكرة باستخدام أشياء آمنة وموجودة يقدر يشوفها.'],
                    ['code' => 'A', 'text' => 'أرسم شكل أو أبتكر مثال يشد انتباهه.'],
                    ['code' => 'E', 'text' => 'أشجعه يجرب وأخليه يتحمس يعرف الإجابة.'],
                    ['code' => 'C', 'text' => 'أقسم الشرح إلى خطوات قصيرة وأرتبها بشكل واضح.'],
                ],
            ],
    
            'Q05' => [
                5,
                'لاحظت بين الأصحاب أو في الحارة مشكلة سلبية تتكرر، مثل رمي المخلفات في مكان غير مناسب أو غيرها من التصرفات السلبية. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'I', 'text' => 'أبحث عن سبب تكرار المشكلة وإيش اللي يزيدها أو يخففها.'],
                    ['code' => 'A', 'text' => 'أسوي عبارة أو رسم بسيط يلفت الانتباه للحل.'],
                    ['code' => 'S', 'text' => 'أتكلم مع الناس بهدوء وأسمع إيش اللي يصعب عليهم.'],
                    ['code' => 'E', 'text' => 'أدعو مجموعة صغيرة لخطوة مشتركة وأشجعها تبدأ.'],
                ],
            ],
    
            'Q06' => [
                6,
                'انتهى شرح أحد الدروس، لكن بقيت فكرة ما فهمتها بشكل واضح. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أطبقها على مثال بيدي عشان أشوف كيف تشتغل.'],
                    ['code' => 'I', 'text' => 'أراجع الأمثلة وأحاول أعرف أي جزء سبب لي الصعوبة.'],
                    ['code' => 'A', 'text' => 'أرسم الفكرة أو أربطها بصورة تساعدني أفهمها.'],
                    ['code' => 'S', 'text' => 'أطلب من المدرس أو أحد زملائي يشرحها لي، وبعدها أناقشها معه.'],
                ],
            ],
    
            'Q07' => [
                7,
                'اتفقت مع مجموعة من زملائك تراجعوا مع بعض، لكن الوقت بدأ يضيع بدون إنجاز واضح. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'I', 'text' => 'أبحث عن سبب ضياع الوقت وأقترح طريقة نجربها.'],
                    ['code' => 'S', 'text' => 'أساعد اللي ما فهم وأشجعه يشارك ويسأل.'],
                    ['code' => 'E', 'text' => 'أبدأ النقاش وأشجع المجموعة تلتزم بالمراجعة.'],
                    ['code' => 'C', 'text' => 'أقسم الوقت والمواضيع وأتابع إيش قد أنجزنا.'],
                ],
            ],
    
            'Q08' => [
                8,
                'قبل الاختبار، لقيت دفاترك وأوراق المراجعة مش مرتبة وبعض المعلومات صعب تلاقيها. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أفحص الدفاتر والأوراق وأتأكد إنها كاملة.'],
                    ['code' => 'I', 'text' => 'أراجع المحتوى عشان أعرف المهم من المكرر أو غير الواضح.'],
                    ['code' => 'S', 'text' => 'أطلب من زملائي اللي ناقصني وأساعدهم باللي ناقصهم.'],
                    ['code' => 'C', 'text' => 'أصنف الأوراق حسب المادة والموضوع وأرتبها بشكل واضح.'],
                ],
            ],
    
            'Q09' => [
                9,
                'لاحظت أنت وبعض زملائك إن درجاتكم نزلت في إحدى المواد. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'I', 'text' => 'أراجع الأخطاء اللي وقعنا فيها وأحاول أعرف إيش السبب اللي خلانا نغلط.'],
                    ['code' => 'A', 'text' => 'أسوي أمثلة أو رسومات بسيطة تساعدني أفهم الدرس وأتذكره.'],
                    ['code' => 'S', 'text' => 'أجلس مع زملائي ونراجع مع بعض، وأساعد اللي ما فهم.'],
                    ['code' => 'C', 'text' => 'أرتب الدروس اللي نحتاج نراجعها وأحدد من وين نبدأ وإيش نكمل بعده.'],
                ],
            ],
    
            'Q10' => [
                10,
                'الفصل يحتاج إعادة ترتيب المقاعد والكتب قبل ما تبدأ الدراسة. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أشارك في نقل الأشياء وترتيبها بشكل عملي.'],
                    ['code' => 'A', 'text' => 'أهتم إن شكل الفصل يكون مرتب ومريح.'],
                    ['code' => 'S', 'text' => 'أراعي احتياجات زملائي وأسألهم عن الترتيب الأنسب.'],
                    ['code' => 'E', 'text' => 'أنظم المشاركة وأشجع الجميع يساعدوا.'],
                ],
            ],
    
            'Q11' => [
                11,
                'عندك كم ساعة فاضية، وما معك أي شغل لازم تخلصه. أي شيء غالبًا تختار تسويه؟',
                [
                    ['code' => 'R', 'text' => 'أصلح شيء بسيط أو أسوي شيء بيدي.'],
                    ['code' => 'A', 'text' => 'أرسم أو أصمم أو أكتب شيء من خيالي.'],
                    ['code' => 'E', 'text' => 'أبدأ رحلة مع أصحابي وأشجعهم يشاركوا معي.'],
                    ['code' => 'C', 'text' => 'أرتب كتبي أو أغراضي أو الأشياء اللي أحتفظ بها.'],
                ],
            ],
    
            'Q12' => [
                12,
                'تريد تشتري غرض تحتاجه، وقدامك أكثر من خيار قريب من بعض. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أشوف الغرض بنفسي وأتأكد من جودته وإنه مناسب للاستخدام.'],
                    ['code' => 'I', 'text' => 'أبحث عن الغرض قبل ما أروح، وأشوف جودته وأسعاره عشان أعرف المناسب.'],
                    ['code' => 'E', 'text' => 'أتكلم مع البائع وأحاول أوصل معه لسعر أو خيار مناسب.'],
                    ['code' => 'C', 'text' => 'أحدد ميزانيتي وأرتب المواصفات اللي أحتاجها قبل ما أشتري.'],
                ],
            ],
    
            'Q13' => [
                13,
                'اختلف كم واحد من أصحابك على موضوع بينهم، والخلاف بدأ يأثر على علاقتهم. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'I', 'text' => 'أحاول أفهم سبب الخلاف وأفرق بين اللي حصل فعلًا وسوء الفهم.'],
                    ['code' => 'S', 'text' => 'أسمع لكل واحد منهم وأساعدهم يفهموا بعض.'],
                    ['code' => 'E', 'text' => 'أبادر وأجمعهم وأحاول أوصلهم لحل يتفقوا عليه.'],
                    ['code' => 'C', 'text' => 'أحدد نقاط الاتفاق والخلاف وأرتب الحلول الممكنة.'],
                ],
            ],
    
            'Q14' => [
                14,
                'في مساحة صغيرة ما أحد يستفيد منها، وممكن تتحول لمكان مفيد. أي مساهمة تشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أنظف المكان وأشارك في تجهيزه بشكل عملي.'],
                    ['code' => 'A', 'text' => 'أتخيل شكل جديد للمكان وأضيف له لمسة حلوة.'],
                    ['code' => 'S', 'text' => 'أسأل الناس اللي بيستخدموه إيش يحتاجوا وأراعي راحتهم.'],
                    ['code' => 'E', 'text' => 'أقنع الآخرين بالفكرة وأنظم مشاركتهم في تنفيذها.'],
                ],
            ],
    
            'Q15' => [
                15,
                'مريت بتجربة مهمة أو شفت موقف بقي في بالك. كيف غالبًا تحب تعبر عنه؟',
                [
                    ['code' => 'A', 'text' => 'أحوله لرسم أو قصة أو فكرة جديدة.'],
                    ['code' => 'S', 'text' => 'أتكلم عنه مع شخص قريب وأسمع تجربته كمان.'],
                    ['code' => 'E', 'text' => 'أحكيه قدام الآخرين بطريقة تشد انتباههم.'],
                    ['code' => 'C', 'text' => 'أرتب اللي حصل من البداية للنهاية وأحكيه بشكل واضح ومرتب.'],
                ],
            ],
    
            'Q16' => [
                16,
                'لاحظت إن نبتة كانت تنمو تمام، وبعد فترة بدأت تذبل رغم إنهم ما زالوا يسقوها. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أفحص التربة ومكان النبتة وكمية الماء، وأجرب أغير شيء واحد.'],
                    ['code' => 'I', 'text' => 'أقارن حالتها قبل والآن عشان أعرف السبب الأقرب.'],
                    ['code' => 'A', 'text' => 'أرسم أو أوضح كيف تغيرت النبتة من قبل إلى الآن.'],
                    ['code' => 'S', 'text' => 'أشرح للي يهتم بها إيش لاحظت وأساعده يتابع حالتها.'],
                ],
            ],
    
            'Q17' => [
                17,
                'في مشكلة بسيطة تتكرر في البيت، لكن مش كل مرة، مثل تسرب ماء أو لمبة تشتغل مرة ومرة لا. أي تصرف يشبهك أكثر؟',
                [
                    ['code' => 'R', 'text' => 'أحاول أصلح المشكلة بنفسي وأجرب أكثر من حل لين أشوف إيش ينفع.'],
                    ['code' => 'I', 'text' => 'أقارن متى تظهر المشكلة ومتى ما تظهر عشان أعرف السبب.'],
                    ['code' => 'E', 'text' => 'أشرح لأهل البيت إيش وصلت له وأقترح الحل اللي أشوفه مناسب.'],
                    ['code' => 'C', 'text' => 'أسجل متى حصلت المشكلة وإيش جربنا وإيش كانت النتيجة.'],
                ],
            ],
    
            'Q18' => [
                18,
                'سمعت معلومة شائعة يكررها كثير من الناس، وبعدها عرفت إنها ناقصة أو مش دقيقة. كيف تفضل توضحها؟',
                [
                    ['code' => 'A', 'text' => 'أسوي مثال أو رسم بسيط يوضح الفرق بين المعلومة الصح والغلط.'],
                    ['code' => 'S', 'text' => 'أتكلم مع الناس بهدوء وأحاول أفهم ليش مقتنعين بها.'],
                    ['code' => 'E', 'text' => 'أبادر وأوضح لهم المعلومة الصحيحة بطريقة تقنعهم.'],
                    ['code' => 'C', 'text' => 'أجمع المعلومات الصحيحة وأرتبها بشكل واضح قبل ما أشاركها.'],
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
