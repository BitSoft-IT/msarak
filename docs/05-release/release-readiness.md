<div dir="rtl" lang="ar">

# Release Readiness — جاهزية الإصدار

**المشروع:** مسارك  
**الإصدار المستهدف:** `v1.0.0`  
**المالك:** H-05 — التكامل النهائي وإدارة الإصدار  
**الحالة:** Working Contract للحزمة الخامسة  
**الغرض:** توحيد نطاق الإصدار، تسليمات أصحاب المهام، تصنيف العيوب، وصيغة الأدلة قبل الوصول إلى Release Gate النهائي.

---

## 1. نطاق الإصدار — Release Scope

يدخل في `v1.0.0` كل ما تم اعتماده وتنفيذه واختباره ضمن الحزم الحالية للمشروع، وبالأخص:

- Authentication وRoles.
- دليل التخصصات والتفاصيل والمقارنة.
- رحلة التقييم كاملة:
  - Start
  - Save
  - Resume
  - Complete
- Scoring v1.2.
- Recommendations.
- Result.
- Profile.
- Result History.
- Historical Result stability.
- Admin Backend.
- Assessment Versions.
- Draft / Publish.
- Statistics.
- Admin UI.
- Responsive / RTL / Accessibility الأساسية.
- Security / Ownership / IDOR protection.
- UAT والتدقيق النهائي للمحتوى.
- التوثيق المطلوب للتسليم والإصدار.

### خارج نطاق `v1.0.0`

أي عنصر غير معتمد ضمن Requirements أو Issues الحالية لا يدخل الإصدار تلقائيًا.

الأمثلة التي لا تدخل إلا بقرار واضح:
- Features جديدة غير مرتبطة بالـRelease الحالي.
- تغيير Scoring أو Recommendation بعد استقراره.
- تغيير معماري كبير.
- API عامة أو تطبيق جوال.
- CRUD للتخصصات من لوحة الإدارة إذا لم يكن ضمن النطاق المعتمد.
- تحسينات شكلية غير مؤثرة يمكن تأجيلها.

أي عنصر جديد مؤثر بعد تثبيت النطاق يعامل كـ:
- Change Request
- أو Deferred Item
- أو Issue مستقلة حسب طبيعته.

---

## 2. قاعدة الإصدار — Release Rule

لا يعتبر أي جزء جاهزًا للإصدار لمجرد دمجه في `main`.

يجب أن يكون:

```text
Implemented
→ Reviewed
→ Integrated
→ Verified
→ Evidence Available
→ No Blocking Defect
```

ولا ينشأ Tag `v1.0.0` قبل اجتياز Release Gate النهائي في H-05.

---

## 3. عقود التسليم — Handoff Contracts

كل صاحب Issue في الحزمة الخامسة يسلم ناتجًا واضحًا يمكن استخدامه مباشرة في Release Gate.

---

### B-05 — Backend / Admin Contract

**الحالة الحالية:** مكتمل ومندمج.

يعتبر B-05 مصدر Backend المعتمد للحزمة الخامسة، ويجب أن تكون العقود التالية مستقرة:

- Assessment Versions.
- Draft management.
- Question management.
- Publish.
- Authorization.
- Statistics.
- Historical stability.

### ما تعتمد عليه بقية الفرق من B-05

- F-05 تعتمد على Backend الحقيقي، وليس Mock logic.
- Q-05 تتحقق من السلوك والتكامل.
- H-05 تعتمد على استقرار العقود قبل Release Candidate.

أي تغيير جديد في B-05 بعد اعتماده يجب تقييم أثره على:
- F-05
- Q-05
- H-05

---

### F-05 — Frontend / Final UX Handoff

يسلم F-05 إلى H-05 وQ-05 واجهات نهائية متكاملة مع Backend.

يجب أن يتضمن التسليم:

- Admin UI.
- Profile UI.
- Result / History integration عند ارتباطها بالنطاق.
- Loading states.
- Empty states.
- Success states.
- Validation errors.
- Forbidden states.
- Not Found states.
- Server Error states.
- RTL.
- Responsive.
- Keyboard / Focus الأساسية.
- عدم وجود Business Logic خاص بالـBackend داخل Frontend.

### Evidence المطلوبة من F-05

على الأقل:

```text
Desktop screenshots
Mobile screenshots
Build result
Relevant automated test result
Critical flow notes
Any known visual limitation
```

لا يحتاج F-05 إعادة شرح Backend logic داخل Evidence.

---

### Q-05 — Technical Verification Handoff

Q-05 هي مصدر الحقيقة للتحقق التقني النهائي.

يجب أن تسلم إلى H-05:

- Technical Verification Status.
- Full Regression Status.
- Security Regression Status.
- Clean Clone Result.
- Critical Flow Verification.
- Open Defects.
- Retest Status.
- Release Blockers.
- Evidence links or records.

### المجالات المطلوبة

#### Student flow

```text
Register/Login
→ Assessment
→ Save
→ Resume
→ Complete
→ Scoring
→ Recommendation
→ Result
→ Profile
→ Result History
→ Historical Result
```

#### Admin flow

```text
Admin Login
→ Versions
→ Draft
→ Questions
→ Validation
→ Publish
→ Statistics
```

#### Security

```text
Unauthenticated → Protected route denied
Student → Admin denied
User A → User B Session denied
User A → User B Result denied
Changing IDs → No data disclosure
Client user_id → No ownership change
```

Q-05 لا تصلح Feature بدل صاحبها. عند Failure تنشئ أو تربط Defect وتعيد الاختبار بعد الإصلاح.

---

### C-05 — UAT / Content Handoff

C-05 هي مصدر User Acceptance وContent Readiness.

يجب أن تسلم إلى H-05:

- UAT Status.
- UAT Evidence.
- Open UAT Issues.
- Retest Status.
- Content Audit Status.
- User Guide Status.
- FAQ Status.
- Source Review Status.

### ما يجب أن يغطيه UAT

#### الطالب

- فهم الصفحة الرئيسية.
- التسجيل والدخول.
- بدء التقييم.
- فهم التعليمات.
- الإجابة والتنقل.
- الحفظ والاستئناف.
- الإكمال.
- فهم Result.
- فهم RIASEC.
- فهم التوصيات.
- فتح تخصص مقترح.
- فتح Profile.
- فتح نتيجة تاريخية.

#### المدير

عند دخوله ضمن النسخة النهائية:

- فهم Dashboard.
- Assessment Versions.
- Draft / Published.
- إدارة Draft Questions.
- Publish.
- Statistics.
- رسائل الخطأ والرفض.

---

## 4. تصنيف العيوب — Defect Classification

يستخدم التصنيف التالي في الحزمة الخامسة والإصدار النهائي.

| Severity | المعنى | قرار الإصدار |
|---|---|---|
| Critical | تسريب بيانات، كسر أمان، تعطل الرحلة الأساسية، فساد بيانات، أو فشل يمنع استخدام النظام | Release Blocker |
| High | خلل رئيسي في Business Logic أو Security أو Integration بدون بديل عملي مقبول | Blocker إلا إذا تم Deferred بقرار موثق |
| Medium | مشكلة مؤثرة لكن يوجد Workaround واضح ولا تكسر الرحلة الأساسية | يمكن التأجيل مع توثيق |
| Low | مشكلة محدودة أو شكلية أو تحسين غير مؤثر على الوظيفة الأساسية | يمكن التأجيل |

### لا يغلق أي Defect إلا بعد

```text
Fix
→ Retest
→ PASS
```

وليس بمجرد رفع Commit للإصلاح.

---

## 5. Deferred Defect Contract

أي عيب يتم تأجيله يجب أن يحتوي على:

```text
Description
Severity
Reason for defer
Owner
Current status
Target version
Impact
Decision
```

ولا يسمح بتأجيل Critical.

High يحتاج قرارًا صريحًا ومبررًا من Release Gate.

---

## 6. صيغة الأدلة — Evidence Format

يستخدم هذا القالب في Q-05 وC-05 وأي تحقق مهم يدخل Release Gate:

```text
ID:
Area:
Scenario / Check:
Preconditions:
Steps:
Expected:
Actual:
Result: PASS / FAIL
Commit SHA:
Evidence:
Linked Issue:
Retest:
Notes:
```

### مثال مختصر

```text
ID: Q05-STU-04
Area: Assessment Resume
Scenario / Check: استئناف جلسة تقييم غير مكتملة

Preconditions:
Student logged in with an in-progress session.

Steps:
1. Open assessment.
2. Answer several questions.
3. Exit.
4. Login again.
5. Resume assessment.

Expected:
Saved answers and progress are restored.

Actual:
Saved answers and progress restored correctly.

Result: PASS
Commit SHA: <sha>
Evidence: Screenshot / test output / PR link
Linked Issue: —
Retest: —
Notes: —
```

---

## 7. Evidence Rules

الدليل الجيد يجب أن يكون:

- مرتبطًا بحالة فعلية.
- قابلًا للتحقق.
- مرتبطًا بـCommit SHA عند الحاجة.
- لا يعتمد على الذاكرة أو وصف شفهي فقط.
- لا يعيد كتابة نفس الدليل في أكثر من ملف بلا حاجة.
- يستخدم Screenshot عندما تكون المشكلة أو النتيجة بصرية.
- يستخدم Test Output أو CI عندما تكون المسألة تقنية.
- يستخدم Issue/PR عند الحاجة لتتبع Defect أو Fix.

---

## 8. Release Blockers

يعتبر العنصر Release Blocker إذا تحقق أحد التالي:

- Critical defect مفتوح.
- High defect غير محسوم.
- فشل Security verification.
- فشل Critical student flow.
- فشل Critical admin flow داخل نطاق الإصدار.
- فشل Clean Clone.
- فشل `migrate:fresh --seed`.
- فشل `npm run build`.
- فشل `php artisan test`.
- تعارض Backend/Frontend Contract مؤثر.
- UAT Failure يمنع إكمال رحلة أساسية.
- Secrets أو بيانات حساسة داخل المستودع.
- Release Candidate غير قابل لإعادة البناء.

---

## 9. ما لا يعتبر Blocker تلقائيًا

لا يوقف الإصدار تلقائيًا:

- اختلاف بصري صغير.
- نص غير مثالي لكن صحيح وغير مضلل.
- تحسين UX غير ضروري لإكمال الرحلة.
- مشكلة Low.
- Feature غير موجودة أصلًا وخارج النطاق.
- Refactor غير مطلوب.
- تحسين Performance غير مرتبط بـNFR معتمد.

هذه تسجل كـKnown Issue أو Deferred Item عند الحاجة.

---

## 10. العلاقة بين أصحاب الحزمة الخامسة

```text
B-05
Backend contracts
      ↓
F-05
Final UI integration
      ↓
Q-05
Technical verification
      ↓
C-05
UAT / Content readiness
      ↓
H-05
Release Gate
      ↓
v1.0.0
```

التنفيذ قد يعمل بالتوازي، لكن اعتماد الإصدار النهائي لا يتجاوز هذه الأدلة.

---

## 11. مسؤولية H-05

H-05 لا تعيد تنفيذ أعمال الآخرين.

H-05 مسؤولة عن:

- Integration status.
- Release scope.
- Release readiness.
- Configuration review.
- Release evidence review.
- Defect state review.
- Known Issues / Deferred Items.
- Release Candidate.
- Final Release Gate.
- Tag `v1.0.0`.

---

## 12. حالة الجاهزية الحالية — Working Status

يتم تحديث هذا القسم أثناء تقدم الحزمة الخامسة.

| Area | Owner | Status | Evidence Ready | Blocker |
|---|---|---|---|---|
| B-05 Backend/Admin | Malek711 | Completed | Pending final reuse | No |
| F-05 Frontend/Admin/Profile | ayman-albaidahi | In Progress | No | TBD |
| Q-05 Technical Verification | Abdullah-Al-basheri | In Progress | No | TBD |
| C-05 UAT/Content | Mulatef-Aldahia | In Progress | No | TBD |
| H-05 Release Integration | Alhareith | In Progress | Partial | TBD |

> هذا الجدول ليس بديلًا عن GitHub Issues، بل ملخص Release Readiness فقط.

---

## 13. شروط الانتقال إلى Final Release Gate

لا يبدأ الإغلاق النهائي قبل توفر:

- F-05 final integration.
- Q-05 Technical Verification Evidence.
- C-05 UAT Evidence.
- Open Defects reviewed.
- No unresolved Critical defect.
- High defects resolved or formally deferred.
- Clean Clone result.
- CI success.
- Final documentation status.

---

## 14. مراجع العمل

يعتمد هذا الملف على:

```text
Issue #39 — H-05
Issue #40 — B-05
Issue #41 — F-05
Issue #42 — Q-05
Issue #43 — C-05

docs/01-management/05-engineering-governance.md
docs/01-management/06-decision-log.md
docs/01-management/07-risk-register.md
docs/02-system-design/04-system-contracts.md
```

عند التعارض، تطبق قواعد Source of Truth المعتمدة في Engineering Governance.

---

# English Summary

## Purpose

This document is the shared release-readiness contract for milestone 5 and the upcoming `v1.0.0` release.

It defines:

- Release scope.
- Team handoff requirements.
- Defect severity rules.
- Evidence format.
- Release blockers.
- H-05 responsibilities.

---

## Release Scope

`v1.0.0` includes the approved and verified implementation of:

- Authentication and roles.
- Specialization catalog, details and comparison.
- Assessment journey.
- Scoring v1.2.
- Recommendations.
- Results.
- Profile and result history.
- Historical result stability.
- Admin backend.
- Assessment versions and publishing.
- Statistics.
- Admin UI.
- Responsive / RTL / basic accessibility.
- Security and ownership protection.
- UAT and final content audit.
- Required release documentation.

Unapproved new features are not automatically part of `v1.0.0`.

---

## Release Rule

A merged feature is not automatically release-ready.

The required flow is:

```text
Implemented
→ Reviewed
→ Integrated
→ Verified
→ Evidence Available
→ No Blocking Defect
```

---

## Handoff Contracts

### F-05

Must provide:

- Final Admin/Profile UI.
- Backend integration.
- Loading / Empty / Success / Error states.
- Responsive / RTL.
- Basic accessibility.
- Desktop and mobile screenshots.
- Build and relevant test status.

### Q-05

Must provide:

- Technical verification status.
- Regression status.
- Security regression.
- Clean clone result.
- Critical flow verification.
- Open defects.
- Retest status.
- Release blockers.
- Evidence.

### C-05

Must provide:

- UAT status.
- UAT evidence.
- Open UAT issues.
- Retest status.
- Content audit.
- User guide status.
- FAQ status.
- Source review status.

### B-05

Acts as the stable backend contract for the milestone.

---

## Defect Classification

| Severity | Meaning | Release Decision |
|---|---|---|
| Critical | Security breach, data corruption, core flow failure | Release Blocker |
| High | Major business/security/integration defect | Blocker unless formally deferred |
| Medium | Important defect with acceptable workaround | May be deferred |
| Low | Limited or visual defect | May be deferred |

A defect is not closed until:

```text
Fix
→ Retest
→ PASS
```

---

## Evidence Format

```text
ID:
Area:
Scenario / Check:
Preconditions:
Steps:
Expected:
Actual:
Result: PASS / FAIL
Commit SHA:
Evidence:
Linked Issue:
Retest:
Notes:
```

---

## Release Blockers

The following block release:

- Open Critical defect.
- Unresolved High defect.
- Security verification failure.
- Critical student/admin flow failure.
- Clean clone failure.
- `migrate:fresh --seed` failure.
- `npm run build` failure.
- `php artisan test` failure.
- Breaking frontend/backend contract mismatch.
- Critical UAT failure.
- Repository secrets.
- Non-reproducible Release Candidate.

---

## H-05 Responsibility

H-05 does not re-run or replace the work of F-05, Q-05 or C-05.

H-05 owns:

- Integration status.
- Release scope.
- Release readiness.
- Configuration review.
- Evidence review.
- Defect status review.
- Known issues / deferred items.
- Release Candidate.
- Final Release Gate.
- `v1.0.0` tag.

</div>