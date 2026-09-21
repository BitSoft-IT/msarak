<div dir="rtl" lang="ar">

# سجل القرارات — Decision Log
**المشروع:** مسارك — منصة التوجيه الأكاديمي والمهني لطلاب الثانوية  
**الحالة:** معتمد  
**آخر تحديث:** 2026-09-21

## الهدف
يوثق هذا الملف القرارات المؤثرة فقط: النطاق، العقود، البيانات، التقييم، الأمان، المعمارية، طريقة العمل والإصدار. لا تسجل القرارات اليومية الصغيرة.

## حالات القرار
`Active` معتمد حاليًا — `Superseded` استبدله قرار أحدث — `Rejected` مرفوض — `Deferred` مؤجل.

## DEC-001 — اعتماد Assessment v1.2
**الحالة:** Active  
المرجع الحالي لنموذج التقييم والحساب هو `docs/04-assessment/question_bank_specification.md` v1.2. يعتمد `response_type = option | none | cannot_judge` و`primary_option_id` وتقييمات اختيارية من `-2..+2`. الفرق بين `NULL` و`0` محفوظ. `cannot_judge` مستبعد من الحساب، ولا تنتج نتيجة إذا كان `ValidQuestions < 15`. أي منطق قديم يعتمد Closest/Least يعد مستبدلًا عند التعارض.

## DEC-002 — عقد موحد لرحلة التقييم
**الحالة:** Active  
المرجع المشترك بين Backend وFrontend والاختبارات هو `docs/02-system-design/04-system-contracts.md`. أي تغيير في Request أو Response أو Error Code أو Session State أو Ownership يجب أن يحدث في العقد قبل التنفيذ أو ضمن نفس التغيير. تعتمد عليه B-03 وF-03 وQ-03 وما بعدها.

## DEC-003 — لا API عامة في V1
**الحالة:** Active  
النظام تطبيق Laravel واحد يستخدم Blade وFetch وSession Authentication وCSRF عبر `routes/web.php`. لا يستخدم Sanctum أو JWT أو API Tokens أو `/api/v1` في النسخة الأولى. إذا ظهر تطبيق خارجي مستقبلًا ينشأ Change Request مستقل.

## DEC-004 — دليل التخصصات JSON
**الحالة:** Active  
المصدر المعتمد للتخصصات هو `resources/data/specializations.json`. لا ينشأ جدول `specializations` أو CRUD أو لوحة إدارة تخصصات في V1. يقرأ Laravel الملف ويقدم بياناته للواجهة، ولا يقرأه المتصفح مباشرة.

## DEC-005 — الحساب والمطابقة على الخادم
**الحالة:** Active  
يحسب RIASEC داخل `ScoringService` وتنفذ المطابقة داخل `RecommendationService`. JavaScript للعرض والتفاعل فقط. لا ترسل رموز RIASEC أو الأوزان أو معادلات الحساب إلى الطالب أثناء التقييم.

## DEC-006 — النتائج التاريخية ثابتة
**الحالة:** Active  
تحفظ النتيجة والدرجات والتوصيات ونسخ الأسماء والإصدارات وقت الإكمال. لا يعاد حساب نتيجة قديمة عند عرضها، ولا تتغير بسبب تحديث إصدار التقييم أو دليل التخصصات.

## DEC-007 — الإكمال Atomic وIdempotent
**الحالة:** Active  
تنفذ عملية الإكمال داخل Transaction واحدة: تحقق → حساب → توصيات → حفظ Result/Scores/Recommendations → إكمال الجلسة. إذا تكرر الطلب يعاد Result الموجود ولا ينشأ Result جديد. تمنع القيود التكرار غير الصحيح.

## DEC-008 — الأمان والملكية على الخادم
**الحالة:** Active  
تطبق Middleware وPolicies وRole/Ownership checks على الخادم. إخفاء الأزرار في الواجهة لتحسين UX فقط وليس وسيلة أمان. يجب اختبار منع الوصول إلى جلسات ونتائج مستخدمين آخرين.

## DEC-009 — GitHub Flow مبسط
**الحالة:** Active  
المسار المعتمد: `Issue → Branch → Implementation → Commit → Push → PR → Review → CI → Squash Merge → Delete Branch`. لا يستخدم فرع دائم `develop` ولا تطوير مباشر على `main`.

## DEC-010 — Squash Merge
**الحالة:** Active  
تعتمد Pull Requests بطريقة `Squash and merge` بعد نجاح CI والمراجعة ومعالجة الملاحظات المانعة، للحفاظ على تاريخ `main` واضحًا.

## DEC-011 — اعتماد MySQL
**الحالة:** Active  
قاعدة البيانات المعتمدة هي MySQL 8.4.x للتطوير والاختبار والتحقق. لا يعتمد SQLite كبيئة رئيسية لأن القيود والسلوك قد تختلف.

## DEC-012 — عدم استخدام Laravel Breeze
**الحالة:** Active  
تنفذ المصادقة بإمكانات Laravel الأساسية دون Starter Kit. لا يستخدم Breeze أو بديل مشابه في النسخة الحالية.

## DEC-013 — عدم استخدام Soft Deletes
**الحالة:** Active  
لا تستخدم Soft Deletes في V1 لعدم وجود Requirement فعلية للحذف. النتائج والإصدارات المستخدمة تحفظ تاريخيًا ولا تحذف يدويًا.

## DEC-014 — الإحصائيات من البيانات الأصلية
**الحالة:** Active  
تحسب الإحصائيات من الجداول الحالية باستخدام Queries مناسبة. لا ينشأ جدول Statistics مستقل إلا إذا ظهرت حاجة مثبتة لاحقًا.

## DEC-015 — النتائج إرشادية وليست تشخيصًا
**الحالة:** Active  
نتائج RIASEC تستخدم للاستكشاف والتوجيه فقط. لا تعرض كنسبة نجاح أو قرار نهائي أو تشخيص نفسي أو توقع نجاح أكاديمي.

## DEC-016 — حوكمة هندسة برمجيات خفيفة
**الحالة:** Active  
يعتمد المشروع Source of Truth وDoR وDoD وDecision Log وChange Request للتغييرات المؤثرة وRisk Register وRegression وIntegration Testing وUAT وRelease Checklist وFinal Verification. لا تضاف إجراءات أو اجتماعات أو وثائق لا تخدم التنفيذ فعليًا.

## DEC-017 — تحديث مصدر الحقيقة عند التغيير
**الحالة:** Active  
أي تغيير يمس Scope أو Data Model أو Contract أو Scoring أو Recommendation أو Security أو Architecture يتبع: `Decision/Change Request → Update Source of Truth → Update affected Issues → Implement → Verify`. لا يعد التغيير مكتملًا إذا بقيت وثيقة فعالة تناقضه.

## DEC-018 — Regression مستمر
**الحالة:** Active  
بعد التغييرات المؤثرة يعاد تشغيل الاختبارات والفحوص المناسبة. قبل الإصدار تنفذ الحزمة الكاملة، ويضاف Regression Test للـBug عند الحاجة لمنع رجوعه.

## DEC-019 — UAT جزء من Validation
**الحالة:** Active  
تنفذ UAT لرحلات الزائر والطالب والمدير قبل الإصدار، وتسجل النتائج بصيغة `PASS/FAIL + Expected + Actual + Notes + Evidence`. أي مشكلة فعلية تسجل كIssue أو Bug.

## DEC-020 — الإصدار النهائي v1.0.0
**الحالة:** Active  
ينشأ Tag `v1.0.0` فقط بعد نجاح CI والاختبارات والبناء وClean Clone وFinal Verification وUAT وRelease Checklist وعدم وجود عيب Critical مفتوح، مع تحديث README وRelease Notes.

## قالب قرار جديد
`DEC-XXX — العنوان | التاريخ | الحالة | المالك | السياق | القرار | السبب | الأثر | الملفات/Issues المتأثرة | القرار السابق إن وجد`

## قواعد السجل
- تسجل القرارات المؤثرة فقط.
- لا يحذف القرار القديم؛ يحول إلى `Superseded`.
- لا يعتمد قرار ناتج عن AI دون مراجعة بشرية.
- عند التعارض يعتمد أحدث قرار `Active` مع مصدر الحقيقة المعتمد.
- إذا أثر القرار في الكود أو العقود فلا يعد مطبقًا حتى يدمج التغيير في `main`.

</div>