---
name: Change Request
about: تغيير مؤثر على النطاق أو البيانات أو العقود أو الحساب أو الأمان
title: "CR — "
labels: "type:change"
assignees: ""
---

## السبب
اشرح باختصار لماذا نحتاج هذا التغيير.

## التغيير
اشرح ما الذي سيتغير بالضبط.

## الأثر
- [ ] Scope / MVP
- [ ] Requirements
- [ ] Data Model
- [ ] Backend
- [ ] Frontend
- [ ] Contract
- [ ] Scoring / Recommendation
- [ ] Authentication / Security
- [ ] Tests
- [ ] Documentation
- [ ] Release / Configuration

## العناصر المتأثرة
### Issues
- 

### الملفات
- 

## المخاطر
- 

## التنفيذ
1. 
2. 
3. 

## التحقق
- [ ] Unit Tests
- [ ] Feature Tests
- [ ] Integration Tests
- [ ] Regression Checks
- [ ] Manual Verification
- [ ] UAT عند الحاجة

## القرار
- [ ] Approved
- [ ] Rejected
- [ ] Deferred

## Decision Log
`DEC-XXX`

---

# CR-001 — اعتماد Assessment v1.2

## السبب
تم استبدال نموذج الإجابة القديم `Closest / Least` بنموذج Assessment v1.2 لتوحيد بنك الأسئلة، قاعدة البيانات، العقود، الواجهة، الاختبارات وحساب RIASEC.

المرجع المعتمد:
`docs/04-assessment/question_bank_specification.md`

## التغيير
يعتمد النموذج الحالي:

- `response_type = option | none | cannot_judge`
- `primary_option_id` عند `option`
- Ratings اختيارية من `-2..+2`
- `NULL` = لم يقدم Rating
- `0` = Rating محايد
- `none` موقف صالح بدون Primary
- `cannot_judge` مستبعد من الحساب
- الحد الأدنى: `ValidQuestions >= 15`
- إضافة `answer_option_ratings`

ويعتبر النموذج القديم التالي مستبدلًا:

- `closest_option_id`
- `least_option_id`
- `closest_weight`
- `least_weight`

## الحساب
لكل مجال:

`P_d = K_d / N_d`  
`Rraw_d = Σr_d / (M_d + 2)`  
`R_d = (Rraw_d + 2) / 4`  
`C_d = M_d / N_d`  
`W_d = 0.30 × C_d`  
`Score_d = 100 × ((1 - W_d) × P_d + W_d × R_d)`

## الأثر
- [x] Requirements
- [x] Data Model
- [x] Backend
- [x] Frontend
- [x] Contract
- [x] Scoring / Recommendation
- [x] Tests
- [x] Documentation
- [x] Seeders / Test Data
- [ ] Authentication / Security
- [ ] Release / Configuration

## Issues المتأثرة
- `C-01` — بنك الأسئلة v1.2
- `B-01` — Data Model و`answer_option_ratings`
- `Q-01` — Seeders وبيانات الاختبار
- `H-03` — عقود رحلة التقييم
- `B-03` — حفظ واستئناف الإجابات
- `F-03` — واجهة التقييم
- `Q-03` — اختبارات رحلة التقييم
- `B-04` — حساب RIASEC
- `Q-04` — اختبارات الحساب والنتائج
- `B-02 / C-03 / F-04 / C-04` — مراجعة التوافق

## الملفات المتأثرة
- `docs/04-assessment/question_bank_specification.md`
- `docs/02-system-design/03-data-model-and-diagrams.md`
- `docs/02-system-design/04-system-contracts.md`
- `docs/02-system-design/05-detailed-design.md`
- `docs/03-system-analysis/SYSTEM_WORKFLOWS.md`
- `docs/03-system-analysis/SYSTEM_USE_CASES.md`
- `docs/01-management/04-work-plan-25-issues.md`

أي نص يعرض `Closest / Least` كنموذج حالي يجب تحديثه أو وسمه `Superseded`.

## المخاطر
- بقاء وثائق قديمة فعالة.
- اختلاف Frontend وBackend.
- اعتبار `NULL` مساويًا لـ`0`.
- إدخال `cannot_judge` في الحساب.
- تطبيق Ratings بوزن خاطئ.
- بقاء Tests مبنية على النموذج السابق.

## التنفيذ
1. تثبيت v1.2 كمصدر الحقيقة.
2. تحديث Data Model وSeeder.
3. تثبيت عقد H-03.
4. تنفيذ B-03 وF-03.
5. اختبار Q-03.
6. تنفيذ B-04.
7. اختبار Q-04.
8. إزالة أو وسم التعليمات القديمة.
9. تشغيل Regression.

## التحقق
- [x] اعتماد مواصفة v1.2
- [x] تحديث Data Model
- [x] تحديث Question Bank / Seeder
- [x] تثبيت عقد H-03
- [ ] Q-03 Feature Tests
- [ ] Q-04 Unit / Integration Tests
- [ ] Regression Checks
- [ ] Manual Verification
- [ ] مراجعة الوثائق القديمة
- [ ] UAT النهائي

## القرار
- [x] Approved
- [ ] Rejected
- [ ] Deferred

## Decision Log
- `DEC-001`
- `DEC-002`
- `DEC-005`
- `DEC-017`

## النتيجة
Assessment v1.2 هو النموذج الوحيد المعتمد حاليًا. أي كود أو Issue أو وثيقة ما زالت تعتمد `Closest / Least` كنموذج حالي يجب تحديثها قبل الاعتماد أو الدمج.