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
                'scenario' => 'تستعد الأسرة أو بعض الجيران لمناسبة بسيطة، ومكان المناسبة يحتاج شوية تجهيز. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أجهز الأشياء المطلوبة وأساعد في ترتيبها بشكل عملي.'],
                    2 => ['code' => 'I', 'text' => 'أراجع الاحتياجات وأفكر إيش ممكن ينقص أو يسبب مشكلة.'],
                    3 => ['code' => 'A', 'text' => 'أهتم بشكل المكان وتناسق الألوان والترتيب.'],
                    4 => ['code' => 'C', 'text' => 'أرتب الشغل بين الموجودين وأتابع إيش قد انعمل.'],
                ],
            ],
    
            2 => [
                'scenario' => 'خصصوا مكان في البيت لحفظ الكتب والأغراض، لكن بعضها ما يزال يضيع أو يتلف. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أفحص المكان وأجرب ترتيب عملي أفضل.'],
                    2 => ['code' => 'I', 'text' => 'أبحث عن سبب المشكلة، مثل الرطوبة أو الزحمة أو كثرة الاستخدام.'],
                    3 => ['code' => 'A', 'text' => 'أبتكر شكل جديد يخلي المكان أوضح وأجمل.'],
                    4 => ['code' => 'C', 'text' => 'أصنف الأغراض وأحدد مكان ثابت لكل نوع.'],
                ],
            ],
    
            3 => [
                'scenario' => 'تأخر وصول الماء للبيت أو الحارة، والموجود صار قليل. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أتأكد من المياه المتبقية وأرتب استخدام الماء الموجود بشكل عملي.'],
                    2 => ['code' => 'I', 'text' => 'أحاول أعرف سبب التأخر وهل المشكلة عامة أو في مكان معين.'],
                    3 => ['code' => 'S', 'text' => 'أساعد أفراد الأسرة أو الجيران الأكثر حاجة في تدبير الموجود.'],
                    4 => ['code' => 'E', 'text' => 'أتواصل مع المعنيين أو أنسق مع غيري عشان نعرف حل مشترك.'],
                ],
            ],
    
            4 => [
                'scenario' => 'سألك طفل أصغر منك عن كيف يشتغل شيء بسيط في البيت أو عن سبب حصول شيء يشوفه كل يوم. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أوضح له الفكرة باستخدام أشياء آمنة وموجودة يقدر يشوفها.'],
                    2 => ['code' => 'A', 'text' => 'أرسم شكل أو أبتكر مثال يشد انتباهه.'],
                    3 => ['code' => 'E', 'text' => 'أشجعه يجرب وأخليه يتحمس يعرف الإجابة.'],
                    4 => ['code' => 'C', 'text' => 'أقسم الشرح إلى خطوات قصيرة وأرتبها بشكل واضح.'],
                ],
            ],
    
            5 => [
                'scenario' => 'لاحظت بين الأصحاب أو في الحارة مشكلة سلبية تتكرر، مثل رمي المخلفات في مكان غير مناسب أو غيرها من التصرفات السلبية. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'I', 'text' => 'أبحث عن سبب تكرار المشكلة وإيش اللي يزيدها أو يخففها.'],
                    2 => ['code' => 'A', 'text' => 'أسوي عبارة أو رسم بسيط يلفت الانتباه للحل.'],
                    3 => ['code' => 'S', 'text' => 'أتكلم مع الناس بهدوء وأسمع إيش اللي يصعب عليهم.'],
                    4 => ['code' => 'E', 'text' => 'أدعو مجموعة صغيرة لخطوة مشتركة وأشجعها تبدأ.'],
                ],
            ],
    
            6 => [
                'scenario' => 'انتهى شرح أحد الدروس، لكن بقيت فكرة ما فهمتها بشكل واضح. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أطبقها على مثال بيدي عشان أشوف كيف تشتغل.'],
                    2 => ['code' => 'I', 'text' => 'أراجع الأمثلة وأحاول أعرف أي جزء سبب لي الصعوبة.'],
                    3 => ['code' => 'A', 'text' => 'أرسم الفكرة أو أربطها بصورة تساعدني أفهمها.'],
                    4 => ['code' => 'S', 'text' => 'أطلب من المدرس أو أحد زملائي يشرحها لي، وبعدها أناقشها معه.'],
                ],
            ],
    
            7 => [
                'scenario' => 'اتفقت مع مجموعة من زملائك تراجعوا مع بعض، لكن الوقت بدأ يضيع بدون إنجاز واضح. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'I', 'text' => 'أبحث عن سبب ضياع الوقت وأقترح طريقة نجربها.'],
                    2 => ['code' => 'S', 'text' => 'أساعد اللي ما فهم وأشجعه يشارك ويسأل.'],
                    3 => ['code' => 'E', 'text' => 'أبدأ النقاش وأشجع المجموعة تلتزم بالمراجعة.'],
                    4 => ['code' => 'C', 'text' => 'أقسم الوقت والمواضيع وأتابع إيش قد أنجزنا.'],
                ],
            ],
    
            8 => [
                'scenario' => 'قبل الاختبار، لقيت دفاترك وأوراق المراجعة مش مرتبة وبعض المعلومات صعب تلاقيها. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أفحص الدفاتر والأوراق وأتأكد إنها كاملة.'],
                    2 => ['code' => 'I', 'text' => 'أراجع المحتوى عشان أعرف المهم من المكرر أو غير الواضح.'],
                    3 => ['code' => 'S', 'text' => 'أطلب من زملائي اللي ناقصني وأساعدهم باللي ناقصهم.'],
                    4 => ['code' => 'C', 'text' => 'أصنف الأوراق حسب المادة والموضوع وأرتبها بشكل واضح.'],
                ],
            ],
    
            9 => [
                'scenario' => 'لاحظت أنت وبعض زملائك إن درجاتكم نزلت في إحدى المواد. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'I', 'text' => 'أراجع الأخطاء اللي وقعنا فيها وأحاول أعرف إيش السبب اللي خلانا نغلط.'],
                    2 => ['code' => 'A', 'text' => 'أسوي أمثلة أو رسومات بسيطة تساعدني أفهم الدرس وأتذكره.'],
                    3 => ['code' => 'S', 'text' => 'أجلس مع زملائي ونراجع مع بعض، وأساعد اللي ما فهم.'],
                    4 => ['code' => 'C', 'text' => 'أرتب الدروس اللي نحتاج نراجعها وأحدد من وين نبدأ وإيش نكمل بعده.'],
                ],
            ],
    
            10 => [
                'scenario' => 'الفصل يحتاج إعادة ترتيب المقاعد والكتب قبل ما تبدأ الدراسة. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أشارك في نقل الأشياء وترتيبها بشكل عملي.'],
                    2 => ['code' => 'A', 'text' => 'أهتم إن شكل الفصل يكون مرتب ومريح.'],
                    3 => ['code' => 'S', 'text' => 'أراعي احتياجات زملائي وأسألهم عن الترتيب الأنسب.'],
                    4 => ['code' => 'E', 'text' => 'أنظم المشاركة وأشجع الجميع يساعدوا.'],
                ],
            ],
    
            11 => [
                'scenario' => 'عندك كم ساعة فاضية، وما معك أي شغل لازم تخلصه. أي شيء غالبًا تختار تسويه؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أصلح شيء بسيط أو أسوي شيء بيدي.'],
                    2 => ['code' => 'A', 'text' => 'أرسم أو أصمم أو أكتب شيء من خيالي.'],
                    3 => ['code' => 'E', 'text' => 'أبدأ رحلة مع أصحابي وأشجعهم يشاركوا معي.'],
                    4 => ['code' => 'C', 'text' => 'أرتب كتبي أو أغراضي أو الأشياء اللي أحتفظ بها.'],
                ],
            ],
    
            12 => [
                'scenario' => 'تريد تشتري غرض تحتاجه، وقدامك أكثر من خيار قريب من بعض. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أشوف الغرض بنفسي وأتأكد من جودته وإنه مناسب للاستخدام.'],
                    2 => ['code' => 'I', 'text' => 'أبحث عن الغرض قبل ما أروح، وأشوف جودته وأسعاره عشان أعرف المناسب.'],
                    3 => ['code' => 'E', 'text' => 'أتكلم مع البائع وأحاول أوصل معه لسعر أو خيار مناسب.'],
                    4 => ['code' => 'C', 'text' => 'أحدد ميزانيتي وأرتب المواصفات اللي أحتاجها قبل ما أشتري.'],
                ],
            ],
    
            13 => [
                'scenario' => 'اختلف كم واحد من أصحابك على موضوع بينهم، والخلاف بدأ يأثر على علاقتهم. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'I', 'text' => 'أحاول أفهم سبب الخلاف وأفرق بين اللي حصل فعلًا وسوء الفهم.'],
                    2 => ['code' => 'S', 'text' => 'أسمع لكل واحد منهم وأساعدهم يفهموا بعض.'],
                    3 => ['code' => 'E', 'text' => 'أبادر وأجمعهم وأحاول أوصلهم لحل يتفقوا عليه.'],
                    4 => ['code' => 'C', 'text' => 'أحدد نقاط الاتفاق والخلاف وأرتب الحلول الممكنة.'],
                ],
            ],
    
            14 => [
                'scenario' => 'في مساحة صغيرة ما أحد يستفيد منها، وممكن تتحول لمكان مفيد. أي مساهمة تشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أنظف المكان وأشارك في تجهيزه بشكل عملي.'],
                    2 => ['code' => 'A', 'text' => 'أتخيل شكل جديد للمكان وأضيف له لمسة حلوة.'],
                    3 => ['code' => 'S', 'text' => 'أسأل الناس اللي بيستخدموه إيش يحتاجوا وأراعي راحتهم.'],
                    4 => ['code' => 'E', 'text' => 'أقنع الآخرين بالفكرة وأنظم مشاركتهم في تنفيذها.'],
                ],
            ],
    
            15 => [
                'scenario' => 'مريت بتجربة مهمة أو شفت موقف بقي في بالك. كيف غالبًا تحب تعبر عنه؟',
                'options' => [
                    1 => ['code' => 'A', 'text' => 'أحوله لرسم أو قصة أو فكرة جديدة.'],
                    2 => ['code' => 'S', 'text' => 'أتكلم عنه مع شخص قريب وأسمع تجربته كمان.'],
                    3 => ['code' => 'E', 'text' => 'أحكيه قدام الآخرين بطريقة تشد انتباههم.'],
                    4 => ['code' => 'C', 'text' => 'أرتب اللي حصل من البداية للنهاية وأحكيه بشكل واضح ومرتب.'],
                ],
            ],
    
            16 => [
                'scenario' => 'لاحظت إن نبتة كانت تنمو تمام، وبعد فترة بدأت تذبل رغم إنهم ما زالوا يسقوها. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أفحص التربة ومكان النبتة وكمية الماء، وأجرب أغير شيء واحد.'],
                    2 => ['code' => 'I', 'text' => 'أقارن حالتها قبل والآن عشان أعرف السبب الأقرب.'],
                    3 => ['code' => 'A', 'text' => 'أرسم أو أوضح كيف تغيرت النبتة من قبل إلى الآن.'],
                    4 => ['code' => 'S', 'text' => 'أشرح للي يهتم بها إيش لاحظت وأساعده يتابع حالتها.'],
                ],
            ],
    
            17 => [
                'scenario' => 'في مشكلة بسيطة تتكرر في البيت، لكن مش كل مرة، مثل تسرب ماء أو لمبة تشتغل مرة ومرة لا. أي تصرف يشبهك أكثر؟',
                'options' => [
                    1 => ['code' => 'R', 'text' => 'أحاول أصلح المشكلة بنفسي وأجرب أكثر من حل لين أشوف إيش ينفع.'],
                    2 => ['code' => 'I', 'text' => 'أقارن متى تظهر المشكلة ومتى ما تظهر عشان أعرف السبب.'],
                    3 => ['code' => 'E', 'text' => 'أشرح لأهل البيت إيش وصلت له وأقترح الحل اللي أشوفه مناسب.'],
                    4 => ['code' => 'C', 'text' => 'أسجل متى حصلت المشكلة وإيش جربنا وإيش كانت النتيجة.'],
                ],
            ],
    
            18 => [
                'scenario' => 'سمعت معلومة شائعة يكررها كثير من الناس، وبعدها عرفت إنها ناقصة أو مش دقيقة. كيف تفضل توضحها؟',
                'options' => [
                    1 => ['code' => 'A', 'text' => 'أسوي مثال أو رسم بسيط يوضح الفرق بين المعلومة الصح والغلط.'],
                    2 => ['code' => 'S', 'text' => 'أتكلم مع الناس بهدوء وأحاول أفهم ليش مقتنعين بها.'],
                    3 => ['code' => 'E', 'text' => 'أبادر وأوضح لهم المعلومة الصحيحة بطريقة تقنعهم.'],
                    4 => ['code' => 'C', 'text' => 'أجمع المعلومات الصحيحة وأرتبها بشكل واضح قبل ما أشاركها.'],
                ],
            ],
        ];
    }
}
