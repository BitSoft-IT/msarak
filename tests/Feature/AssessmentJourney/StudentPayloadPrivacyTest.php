<?php

namespace Tests\Feature\AssessmentJourney;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\AssessmentJourneyFixtures;
use Tests\TestCase;

/**
 * Q-03 — Phase 3: the journey endpoints are a one-way pipe — questions and
 * options go in, plain answers come back. Nothing RIASEC-shaped may ever
 * travel to the browser, because the whole scoring layer is out of scope of
 * the journey contract.
 *
 * tests/Feature/AssessmentSessionTest.php::test_no_riasec_code_or_weights_in_resume_payload
 * already scans questions/options of the resume payload, and AssessmentUiTest
 * asserts the rendered HTML. This file generalises the scan to every journey
 * response including the error envelopes, pins the exact allowed field list,
 * and proves no result table is ever materialised.
 */
class StudentPayloadPrivacyTest extends TestCase
{
    use RefreshDatabase;
    use AssessmentJourneyFixtures;

    /**
     * Keys that would reveal the scoring model. Compared as exact key names,
     * never as substrings, so an unrelated field cannot trip the check.
     */
    private const FORBIDDEN_KEYS = [
        'riasec_code',
        'weight',
        'weights',
        'lambda',
        'score',
        'scores',
        'max_rating',
        'closest_weight',
        'least_weight',
    ];

    public function test_no_riasec_code_or_scoring_constants_leak_from_any_journey_response(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $otherStudent = $this->createStudentUser();
        $admin = $this->createAdminUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $completed = $this->createCompletedSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        $validPayload = $this->createPrimaryAnswerPayload($options[0]->id, [
            ['option_id' => $options[0]->id, 'rating' => 2],
        ]);

        // Act: gather success and error responses from every journey verb.
        // actingAs() persists across requests inside one test, so the auth
        // guards are reset before the guest probe and re-set afterwards.
        $responses = [
            'start' => $this->actingAs($student)->postJson(route('assessment.sessions.store')),
            'show' => $this->actingAs($student)->getJson(route('assessment.show', $session->id)),
            'save' => $this->actingAs($student)->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $validPayload),
        ];

        $this->app['auth']->forgetGuards();

        $responses['guest'] = $this->getJson(route('assessment.show', $session->id));

        $responses += [
            'admin' => $this->actingAs($admin)->getJson(route('assessment.show', $session->id)),
            'other student' => $this->actingAs($otherStudent)->getJson(route('assessment.show', $session->id)),
            'completed session' => $this->actingAs($student)->putJson(route('assessment.answers.update', [
                'assessmentSession' => $completed->id,
                'question' => $question->id,
            ]), $validPayload),
            'invalid answer' => $this->actingAs($student)->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 9],
            ])),
        ];

        // Assert: none of them carries a scoring key at any depth
        foreach ($responses as $label => $response) {
            $keys = [];
            $this->collectKeysRecursive($response->json(), $keys);
            $leaked = array_intersect($keys, self::FORBIDDEN_KEYS);
            $this->assertEmpty(
                $leaked,
                "استجابة {$label} تكشف عن مفاتيح التسجيل: " . implode(', ', $leaked)
            );
        }

        // Sanity: the success paths really answered, so the scan covered live data.
        // The student already holds the fixture session, so startOrResumeSession
        // resumes it and answers 200 with resumed = true instead of 201.
        $responses['start']->assertStatus(200);
        $this->assertTrue($responses['start']->json('data.resumed'));
        $responses['show']->assertStatus(200);
        $responses['save']->assertStatus(200);
        $responses['guest']->assertStatus(401);
        $responses['admin']->assertStatus(403);
        $responses['other student']->assertStatus(404);
        $responses['completed session']->assertStatus(409);
        $responses['invalid answer']->assertStatus(422);
    }

    public function test_question_and_option_payloads_expose_only_the_approved_fields(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        $this->actingAs($student)->putJson(route('assessment.answers.update', [
            'assessmentSession' => $session->id,
            'question' => $question->id,
        ]), $this->createPrimaryAnswerPayload($options[0]->id, [
            ['option_id' => $options[0]->id, 'rating' => 2],
        ]))->assertStatus(200);

        // Act
        $response = $this->actingAs($student)->getJson(route('assessment.show', $session->id));

        // Assert (HTTP): the top-level contract blocks, nothing more
        $response->assertStatus(200);
        $this->assertSame(
            ['session', 'progress', 'questions', 'saved_answers'],
            array_keys($response->json('data')),
            'الكتل المعتمدة فقط هي التي تُرجع.'
        );
        $this->assertSame(
            ['session_id', 'status', 'assessment_version_id'],
            array_keys($response->json('data.session'))
        );
        $this->assertSame(
            ['processed', 'total', 'current_position'],
            array_keys($response->json('data.progress'))
        );

        // Every question exposes exactly its approved fields
        foreach ($response->json('data.questions') as $questionPayload) {
            $this->assertSame(
                ['question_id', 'position', 'scenario', 'options'],
                array_keys($questionPayload),
                'السؤال يعرض الحقول المعتمدة فقط.'
            );
            foreach ($questionPayload['options'] as $optionPayload) {
                $this->assertSame(
                    ['option_id', 'option_text'],
                    array_keys($optionPayload),
                    'الخيار يعرض الحقلين المعتمدين فقط.'
                );
            }
        }

        // Every saved answer exposes exactly its approved fields
        $savedAnswers = $response->json('data.saved_answers');
        $this->assertNotEmpty($savedAnswers, 'يوجد إجابة محفوظة لفحص شكلها.');
        foreach ($savedAnswers as $savedAnswer) {
            $this->assertSame(
                ['question_id', 'primary_option_id', 'none_selected', 'unable_to_judge', 'ratings'],
                array_keys($savedAnswer),
                'الإجابة المحفوظة تعرض الحقول المعتمدة فقط.'
            );
            foreach ($savedAnswer['ratings'] as $ratingPayload) {
                $this->assertSame(
                    ['option_id', 'rating'],
                    array_keys($ratingPayload),
                    'التقييم يعرض الحقلين المعتمدين فقط.'
                );
                $this->assertContains($ratingPayload['rating'], [-2, -1, 0, 1, 2], 'القيمة العددية ضمن المقياس المعتمد.');
            }
        }
    }

    public function test_the_journey_endpoints_never_materialise_result_tables(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        // Act: exercise the whole journey surface, including a rejection.
        // The student already holds the fixture session, so the call resumes it.
        $this->actingAs($student)->postJson(route('assessment.sessions.store'))->assertStatus(200);
        $this->actingAs($student)->getJson(route('assessment.show', $session->id))->assertStatus(200);
        $this->actingAs($student)->putJson(route('assessment.answers.update', [
            'assessmentSession' => $session->id,
            'question' => $question->id,
        ]), $this->createPrimaryAnswerPayload($options[0]->id))->assertStatus(200);
        $this->actingAs($student)->putJson(route('assessment.answers.update', [
            'assessmentSession' => $session->id,
            'question' => $question->id,
        ]), $this->createPrimaryAnswerPayload($options[0]->id, [
            ['option_id' => $options[0]->id, 'rating' => 9],
        ]))->assertStatus(422);

        // Assert (DB): the scoring layer stays absent
        $this->assertSame(0, DB::table('results')->count(), 'لا تُنشأ أية نتيجة عبر الرحلة.');
        $this->assertSame(0, DB::table('result_scores')->count(), 'لا تُنشأ أية درجات عبر الرحلة.');
        $this->assertSame(0, DB::table('result_recommendations')->count(), 'لا تُنشأ أية توصيات عبر الرحلة.');
    }

    private function collectKeysRecursive(mixed $value, array &$keys): void
    {
        if (! is_array($value)) {
            return;
        }

        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $keys[] = $key;
            }
            $this->collectKeysRecursive($item, $keys);
        }
    }
}
