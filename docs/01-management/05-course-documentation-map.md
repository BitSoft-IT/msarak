<div dir="rtl" lang="ar">

# خريطة توثيق متطلبات المقرر

**المشروع:** مسارك — منصة التوجيه الأكاديمي والمهني لطلاب الثانوية في اليمن  
**الغرض:** ربط متطلبات توثيق المقرر بمصادر الحقيقة الموجودة في المشروع دون إنشاء نسخ مكررة.

## القاعدة الحاكمة

إذا كانت المعلومة موثقة بالفعل في وثيقة معتمدة، فلا تُنشأ وثيقة ثانية بالمحتوى نفسه لمجرد أن مستودع المقرر يستخدم اسمًا مختلفًا.

الملفات التشغيلية مثل قوالب GitHub تساعد على تطبيق القواعد، لكنها لا تستبدل وثائق المتطلبات أو التصميم أو الإدارة.

## خريطة المطابقة

| متطلب المقرر | المصدر المعتمد في مسارك |
|---|---|
| Project Brief | `docs/00-baseline/المرحلة-0-وثيقة-التأسيس-المعتمدة.docx` |
| Mini-SRS | `docs/00-baseline/المرحلة_1_مواصفات_المتطلبات_البرمجية.docx` |
| Requirements Worksheet | SRS المعتمدة |
| User Stories / Acceptance Criteria | SRS + `docs/03-system-analysis/SYSTEM_USE_CASES.md` + GitHub Issues |
| Edge Cases | SRS + `SYSTEM_USE_CASES.md` + `SYSTEM_WORKFLOWS.md` |
| Team Roles | `docs/01-management/03-team-roles.md` |
| Git Workflow | `docs/01-management/01-github-team-management.md` |
| GitHub Project Board | `docs/01-management/01-github-team-management.md` |
| Code Review | `docs/01-management/02-code-review.md` |
| Work Plan | `docs/01-management/04-work-plan-25-issues.md` |
| Architecture / Design | `docs/02-system-design/` |
| Use Cases | `docs/03-system-analysis/SYSTEM_USE_CASES.md` |
| Workflows | `docs/03-system-analysis/SYSTEM_WORKFLOWS.md` |
| Assessment Framework | `docs/04-assessment/scientific_theoretical_framework.md` |
| Question Bank | `docs/04-assessment/question_bank_specification.md` |
| AI Usage Log | `AI_Log.md` |
| Deliverables Checklist | `DELIVERABLES_CHECKLIST.md` |

## ترتيب مصادر الحقيقة

عند الحاجة إلى تحديد المرجع الصحيح، يستخدم الترتيب التالي بحسب موضوع القرار:

1. وثيقة التأسيس المعتمدة للنطاق والهدف العام.
2. SRS للمتطلبات وقواعد العمل ومعايير القبول.
3. وثائق إدارة الفريق للأدوار وطريقة العمل.
4. دليل Code Review لجودة المراجعة.
5. القرارات التقنية للقيود التقنية المعتمدة.
6. وثائق التصميم والمعمارية.
7. Use Cases وWorkflows لسلوك الرحلات.
8. وثائق التقييم للمحتوى العلمي ونموذج التقييم.
9. GitHub Issue الحالية لتفاصيل تنفيذ المهمة، بشرط ألا تتعارض مع المصادر المعتمدة.

## قواعد منع التعارض

1. لا تنشأ نسخة جديدة من وثيقة موجودة لمجرد تغيير اسمها.
2. لا يعاد تسمية وثيقة معتمدة فقط لتطابق اسم قالب المقرر.
3. عند الحاجة، يضاف رابط إلى المصدر الحالي بدل نسخ محتواه.
4. أي تغيير في Requirement أو Scope يجب أن يوثق سببه وأثره قبل اعتماده.
5. قوالب GitHub أدوات تشغيلية وليست مصادر حقيقة أعلى من SRS أو القرارات التقنية.
6. الاستخدام المؤثر للذكاء الاصطناعي يسجل في `AI_Log.md`.
7. يمنع وضع أسرار أو بيانات شخصية حساسة أو محتوى `.env` في الوثائق.
8. التعديلات التوثيقية تمر عبر:
   `Issue → Branch → Pull Request → Review → Squash Merge`.
9. قبل التسليم تراجع `DELIVERABLES_CHECKLIST.md`.
10. عند اكتشاف تعارض، لا يُنشأ حل ثالث صامت؛ يسجل التعارض في Issue ويُحسم في المصدر المختص.

## ملفات لم يتم تكرارها عمدًا

لم ننشئ ملفات مستقلة جديدة لـ:

- Project Brief
- Mini-SRS
- Requirements Worksheet
- Team Roles
- Edge Cases
- Git Workflow
- CONTRIBUTING
- Project Board Description

لأن محتواها موجود بالفعل في وثائق المشروع المعتمدة ويجب الحفاظ على مصدر حقيقة واحد.

</div>