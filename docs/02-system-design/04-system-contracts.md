<div dir="rtl" lang="ar">

# المرحلة السابعة — عقود النظام والطلبات والاستجابات

**الملف:** `09-system-contracts.md`  
**الحالة:** معتمدة للتنفيذ  
**آخر تحديث:** 2026-09-11  
**النطاق:** Routes، الصلاحيات، المدخلات، الاستجابات، والأخطاء.  
**خارج النطاق:** كتابة Controllers، تصميم الشاشات، وOpenAPI عامة.

---

## 1. القرار الحاكم

النظام تطبيق Laravel واحد يستخدم:

- **Blade/HTML** لصفحات العرض.
- **Fetch + JSON** لحفظ الإجابات والإكمال وعمليات المدير.
- **Session Authentication** باستخدام إمكانات Laravel الأساسية دون Starter Kit.
- **CSRF** لكل طلب يغير البيانات.
- المسارات في `routes/web.php`، دون `/api` أو Sanctum.

لا توجد API عامة في النسخة الأولى. إذا ظهر تطبيق جوال أو تكامل خارجي لاحقًا، يصمم له `/api/v1` مستقل.

---

## 2. قواعد العقود

### صفحات HTML

- تعيد `200` عند النجاح.
- تعيد Redirect بعد التسجيل والدخول والنماذج التقليدية.
- تعرض صفحة `404` أو `403` المناسبة دون تفاصيل داخلية.

### طلبات Fetch

ترسل:

```http
Accept: application/json
Content-Type: application/json
X-CSRF-TOKEN: <token>
```

استجابة النجاح:

```json
{
  "data": {},
  "message": "تمت العملية بنجاح."
}
```

استجابة الخطأ:

```json
{
  "message": "تعذر تنفيذ العملية.",
  "code": "ERROR_CODE",
  "errors": {
    "field": ["رسالة تحقق واضحة."]
  }
}
```

- `errors` يظهر فقط عند أخطاء الحقول.
- الرسائل للمستخدم بالعربية الواضحة.
- `code` ثابت ليستفيد منه JavaScript والاختبارات.

---

## 3. مسارات الزائر

| الطريقة | المسار | الاسم | الاستجابة | الحالة |
|---|---|---|---|---:|
| GET | `/` | `home` | الصفحة الرئيسية | 200 |
| GET | `/specializations` | `specializations.index` | قائمة التخصصات | 200 |
| GET | `/specializations/compare` | `specializations.compare` | مقارنة تخصصين | 200/422 |
| GET | `/specializations/{specialization}` | `specializations.show` | تفاصيل تخصص | 200/404 |

### عقد المقارنة

Query:

| الحقل | القاعدة |
|---|---|
| `first` | مفتاح تخصص موجود |
| `second` | مفتاح مختلف وموجود |

لا يقبل النظام أكثر أو أقل من تخصصين في النسخة الأولى.

---

## 4. مسارات المصادقة

تعتمد المنصة مسارات المصادقة التالية داخل `routes/web.php`، وتنفذ باستخدام إمكانات Laravel الأساسية:

| الطريقة | المسار | الوظيفة |
|---|---|---|
| GET/POST | `/register` | عرض التسجيل وإنشاء الحساب |
| GET/POST | `/login` | عرض الدخول وتنفيذه |
| POST | `/logout` | إنهاء الجلسة |
| GET/POST | `/forgot-password` | طلب رابط الاستعادة |
| GET | `/reset-password/{token}` | عرض إعادة التعيين |
| POST | `/reset-password` | حفظ كلمة المرور الجديدة |

قواعد إضافية:

- لا يرسل بريد تفعيل.
- البريد فريد.
- رسالة طلب الاستعادة لا تكشف وجود الحساب.
- رابط الاستعادة صالح لمدة لا تتجاوز ساعة ويُبطل بعد الاستخدام.
- تطبق Rate Limiting على الدخول وطلب الاستعادة.

---

## 5. مسارات الطالب

تعمل تحت `auth` و`role:student`.

### صفحات العرض

| الطريقة | المسار | الاسم | الغرض |
|---|---|---|---|
| GET | `/assessment` | `assessment.intro` | المقدمة وحالة الجلسة |
| GET | `/assessment/sessions/{assessmentSession}` | `assessment.show` | عرض الاختبار واستعادة التقدم |
| GET | `/results` | `results.index` | سجل نتائج الطالب |
| GET | `/results/{result}` | `results.show` | عرض نتيجة محفوظة |

### عمليات JSON

| الطريقة | المسار | الاسم | النجاح |
|---|---|---|---|
| POST | `/assessment/sessions` | `assessment.sessions.store` | 201 جديد أو 200 مستأنف |
| PUT | `/assessment/sessions/{assessmentSession}/answers/{question}` | `assessment.answers.update` | 200 |
| POST | `/assessment/sessions/{assessmentSession}/complete` | `assessment.sessions.complete` | 201 أول مرة أو 200 مكرر |

---

## 6. عقد بدء التقييم

### الطلب

لا يحتاج Body. يحدد الخادم الطالب والإصدار النشط.

### الاستجابة

```json
{
  "data": {
    "session_id": 42,
    "status": "in_progress",
    "assessment_version": 3,
    "resumed": false,
    "redirect_url": "/assessment/sessions/42"
  },
  "message": "بدأ الاختبار."
}
```

القواعد:

- إن وجدت جلسة غير مكتملة للإصدار نفسه، تعاد مع `resumed: true`.
- إن لم يوجد إصدار نشط، تعاد `409 ASSESSMENT_UNAVAILABLE`.
- تكرار الطلب لا ينشئ جلسات غير مكتملة مكررة.

---

## 7. عقد صفحة التقييم

لا ترسل للمتصفح رموز RIASEC أو الأوزان.

View Model:

| العنصر | المحتوى |
|---|---|
| `session` | المعرف، الحالة، ورقم الإصدار |
| `progress` | المعالج، المحتسب، المتجاوز، والإجمالي 18 |
| `questions` | المعرف، الموضع، نص الموقف، والخيارات |
| `saved_answers` | الإجابات السابقة اللازمة للاستئناف |

شكل السؤال:

```json
{
  "id": 7,
  "position": 7,
  "scenario": "نص الموقف",
  "options": [
    { "id": 25, "text": "نص الخيار" }
  ]
}
```

ترتيب الخيارات المعروض يمكن خلطه، لكن المعرف لا يتغير.

---

## 8. عقد حفظ الإجابة

### المسار

```http
PUT /assessment/sessions/{session}/answers/{question}
```

### اختيار الأقرب والأقل

```json
{
  "closest_option_id": 25,
  "least_option_id": 27,
  "is_skipped": false
}
```

### تعذر الحكم

```json
{
  "closest_option_id": null,
  "least_option_id": null,
  "is_skipped": true
}
```

### الاستجابة

```json
{
  "data": {
    "question_id": 7,
    "saved": true,
    "progress": {
      "processed": 7,
      "answered": 6,
      "skipped": 1,
      "total": 18
    },
    "saved_at": "2026-09-02T10:30:00Z"
  },
  "message": "حُفظت الإجابة."
}
```

### التحقق

- الجلسة يملكها الطالب وحالتها `in_progress`.
- السؤال يتبع إصدار الجلسة.
- الخياران يتبعان السؤال نفسه ومختلفان.
- عند التجاوز يكون الخياران فارغين.
- الطلب Upsert؛ تكراره يحدث الصف نفسه ولا ينشئ تكرارًا.

---

## 9. عقد إكمال التقييم

### الطلب

```http
POST /assessment/sessions/{session}/complete
```

لا يحتاج Body.

### الاستجابة

```json
{
  "data": {
    "session_id": 42,
    "status": "completed",
    "result_id": 19,
    "result_url": "/results/19"
  },
  "message": "اكتمل الاختبار وحُفظت النتيجة."
}
```

القواعد:

- يجب معالجة 18 موقفًا.
- يجب وجود 15 إجابة محتسبة على الأقل.
- الحساب والمطابقة والحفظ والإكمال داخل Transaction واحدة.
- عند تكرار الطلب بعد النجاح، تعاد النتيجة الموجودة مع `200`.
- لا تعاد الدرجات من هذا الطلب؛ تعرض من صفحة النتيجة.

---

## 10. عقود صفحات النتائج

### سجل النتائج

كل عنصر يحتاج:

| الحقل | الغرض |
|---|---|
| `id` | رابط النتيجة |
| `completed_at` | تاريخ الإكمال |
| `top_domains` | أعلى المجالات باختصار |
| `recommendation_names` | أسماء التخصصات المحفوظة |

### صفحة النتيجة

| العنصر | ما يعرض |
|---|---|
| `domains` | المجالات الستة وترتيبها ووصفها |
| `top_domains` | أعلى ثلاثة مع الحفاظ على التعادل |
| `recommendations` | 3–5 تخصصات وسبب التقارب ورابط الدليل |
| `guidance` | حدود النتيجة وإرشادات النقاش |
| `versions` | إصدار التقييم والكتالوج للاستخدام الداخلي |

لا ترسل `similarity_score` إلى الواجهة، ولا تعرض الدرجة كنسبة نجاح أو يقين.

---

## 11. مسارات المدير

تعمل تحت `auth` و`role:admin` وبادئة `/admin`.

### صفحات العرض

| الطريقة | المسار | الاسم | الغرض |
|---|---|---|---|
| GET | `/admin` | `admin.dashboard` | لوحة مختصرة |
| GET | `/admin/assessment-versions` | `admin.versions.index` | قائمة الإصدارات |
| GET | `/admin/assessment-versions/{assessmentVersion}/edit` | `admin.versions.edit` | تحرير المسودة |
| GET | `/admin/statistics` | `admin.statistics.index` | الإحصائيات المجمعة |

### عمليات JSON

| الطريقة | المسار | الوظيفة | النجاح |
|---|---|---|---:|
| POST | `/admin/assessment-versions` | إنشاء مسودة | 201 |
| POST | `/admin/assessment-versions/{assessmentVersion}/questions` | إضافة سؤال كامل | 201 |
| PUT | `/admin/assessment-versions/{assessmentVersion}/questions/{question}` | استبدال سؤال وخياراته | 200 |
| DELETE | `/admin/assessment-versions/{assessmentVersion}/questions/{question}` | حذف سؤال من مسودة | 204 |
| POST | `/admin/assessment-versions/{assessmentVersion}/publish` | نشر المسودة | 200/201 |

لا توجد مسارات لإدارة محتوى التخصصات أو تعديل نتائج الطلاب.

---

## 12. عقد إنشاء مسودة

### الطلب

```json
{
  "source_version_id": 3
}
```

- الحقل اختياري.
- عند وجوده تُنسخ النسخة وأسئلتها وخياراتها.
- عند غيابه تنشأ مسودة فارغة.
- لا يسمح بأكثر من مسودة واحدة مفتوحة في النسخة الأولى؛ يعاد الموجود بدل إنشاء مكرر.

### الاستجابة

```json
{
  "data": {
    "id": 4,
    "version_number": 4,
    "status": "draft",
    "question_count": 18,
    "edit_url": "/admin/assessment-versions/4/edit"
  },
  "message": "أُنشئت المسودة."
}
```

---

## 13. عقد السؤال الإداري

السؤال وخياراته **وحدة تعديل واحدة**.

### الإنشاء أو الاستبدال

```json
{
  "position": 7,
  "scenario": "نص الموقف",
  "options": [
    {
      "position": 1,
      "text": "نص الخيار الأول",
      "riasec_code": "I",
      "closest_weight": 2,
      "least_weight": -1
    },
    {
      "position": 2,
      "text": "نص الخيار الثاني",
      "riasec_code": "S",
      "closest_weight": 2,
      "least_weight": -1
    },
    {
      "position": 3,
      "text": "نص الخيار الثالث",
      "riasec_code": "E",
      "closest_weight": 2,
      "least_weight": -1
    },
    {
      "position": 4,
      "text": "نص الخيار الرابع",
      "riasec_code": "C",
      "closest_weight": 2,
      "least_weight": -1
    }
  ]
}
```

التحقق:

- الإصدار `draft` فقط.
- الموضع من 1 إلى 18 وفريد.
- السيناريو غير فارغ.
- أربعة خيارات بالضبط.
- مواضع الخيارات 1–4 وفريدة.
- كل رمز من RIASEC.
- لا يتكرر المجال داخل السؤال.
- الأوزان في الإصدار الأول: `+2` و`-1`.

استبدال السؤال يستبدل خياراته داخل Transaction واحدة.

---

## 14. عقد نشر الإصدار

### الطلب

```http
POST /admin/assessment-versions/{version}/publish
```

لا يحتاج Body.

### شروط النشر

- الحالة `draft`.
- 18 سؤالًا بمواضع 1–18.
- أربعة خيارات لكل سؤال.
- 72 خيارًا.
- كل مجال RIASEC يظهر 12 مرة.
- الأوزان مطابقة للنموذج المعتمد.

### الاستجابة

```json
{
  "data": {
    "id": 4,
    "version_number": 4,
    "status": "active",
    "published_at": "2026-09-02T11:00:00Z"
  },
  "message": "نُشر إصدار التقييم."
}
```

النشر المتكرر للإصدار النشط يعيد حالته مع `200`، ولا ينشئ إصدارًا جديدًا.

---

## 15. عقد الإحصائيات

### الطلب

```http
GET /admin/statistics?from=2026-09-01&to=2026-09-30
```

| الحقل | القاعدة |
|---|---|
| `from` | تاريخ اختياري بصيغة YYYY-MM-DD |
| `to` | تاريخ اختياري، ولا يسبق `from` |

View Model:

```json
{
  "started_assessments": 120,
  "completed_assessments": 90,
  "completion_rate": 75.0,
  "top_recommendations": [
    {
      "specialization_key": "information-technology",
      "name": "تقنية المعلومات",
      "count": 24
    }
  ]
}
```

تجمع البيانات فقط، ولا تعرض إجابات أو هوية طالب.

---

## 16. حالات HTTP والأخطاء

| الحالة | الاستخدام |
|---:|---|
| 200 | قراءة، تحديث، استئناف، أو تكرار عملية مكتملة |
| 201 | إنشاء جلسة أو مسودة أو سؤال أو نتيجة لأول مرة |
| 204 | حذف ناجح دون محتوى |
| 401 | غير مسجل في طلب JSON |
| 403 | الدور لا يملك الوظيفة |
| 404 | المورد غير موجود أو لا يملكه المستخدم |
| 409 | تعارض حالة: مكتمل، غير متاح، أو غير قابل للتعديل |
| 422 | مدخلات غير صالحة أو نموذج غير جاهز للنشر |
| 429 | تجاوز حد المحاولات |
| 500 | خطأ داخلي برسالة عامة |

رموز التطبيق:

| الرمز | المعنى |
|---|---|
| `ASSESSMENT_UNAVAILABLE` | لا يوجد إصدار نشط |
| `SESSION_COMPLETED` | محاولة تعديل جلسة مكتملة |
| `SESSION_NOT_READY` | لم تتحقق شروط الإكمال |
| `ANSWER_INVALID` | خيارات أو حالة إجابة غير صحيحة |
| `VERSION_NOT_DRAFT` | محاولة تعديل إصدار ثابت |
| `VERSION_NOT_PUBLISHABLE` | فشل قواعد النشر |
| `FORBIDDEN` | صلاحية غير كافية |
| `RESOURCE_NOT_FOUND` | المورد غير موجود أو غير مملوك |

---

## 17. الصلاحيات والملكية

| المورد | الطالب | المدير |
|---|---|---|
| دليل التخصصات | قراءة | قراءة |
| جلسة الطالب | المالك فقط | لا وصول تشغيلي |
| إجابات الطالب | المالك قبل الإكمال | لا وصول من الواجهة |
| نتيجة الطالب | المالك فقط | لا وصول فردي |
| إصدار التقييم | قراءة الإصدار النشط عبر الاختبار | إدارة المسودة والنشر |
| الإحصائيات | لا | قراءة مجمعة |

- الوصول إلى مورد يخص طالبًا آخر يعيد `404`.
- فشل الدور على وظيفة إدارية يعيد `403`.
- التحقق يتم في Policies/Services، لا في JavaScript.

---

## 18. ربط المسارات بحالات الاستخدام

| حالة الاستخدام | المسارات |
|---|---|
| UC-01 | `specializations.index`، `specializations.show` |
| UC-02 | `specializations.compare` |
| UC-03–05 | مسارات التسجيل والدخول والخروج واستعادة كلمة المرور |
| UC-06 | `assessment.intro`، `assessment.sessions.store`، `assessment.show` |
| UC-07 | `assessment.answers.update` |
| UC-08 | `assessment.sessions.complete` |
| UC-09–10 | `results.show`، `results.index` |
| UC-11 | مسارات `admin.versions.*` والأسئلة والنشر |
| UC-12 | `admin.statistics.index` |

---

## 19. أسماء المسؤوليات البرمجية

| المسؤولية | المكوّن المقترح |
|---|---|
| صفحات الدليل | `SpecializationController` |
| بدء/عرض/إكمال الجلسة | `AssessmentSessionController` |
| حفظ الإجابة | `AssessmentAnswerController` |
| عرض النتائج | `ResultController` |
| إدارة الإصدارات | `Admin/AssessmentVersionController` |
| إدارة السؤال الكامل | `Admin/QuestionController` |
| الإحصائيات | `Admin/StatisticsController` |

Controllers لا تحسب ولا تطابق؛ تستدعي Services المعتمدة في وثيقة المعمارية.

---

## 20. معايير اعتماد المرحلة

تُعتمد المرحلة عند الموافقة على:

- فصل HTML عن عمليات JSON.
- استخدام Session وCSRF دون API عامة.
- مسارات الطالب والمدير وصلاحياتها.
- عقد الإجابة والإكمال.
- تعديل السؤال وخياراته كوحدة واحدة.
- رموز الأخطاء وحالات HTTP.
- عدم كشف الأوزان أو الرموز أو التشابه للطالب.
- عدم وجود مسارات لإدارة التخصصات أو نتائج الطلاب.
- قابلية إعادة طلب البدء والحفظ والإكمال دون تكرار البيانات.

هذه العقود هي المرجع المعتمد لتحويلها إلى Routes وForm Requests وFeature Tests. أي تغيير في مسار أو مدخل أو استجابة يوثق هنا قبل اعتماده في التنفيذ.

</div>
