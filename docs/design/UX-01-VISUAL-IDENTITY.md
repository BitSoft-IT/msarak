# UX-01 — توحيد الهوية البصرية للواجهات (Issue #56) والخطة التنفيذية

> **المسار المقترح في المستودع:** `docs/design/UX-01-VISUAL-IDENTITY.md`
> **الحالة:** جاهز للمراجعة قبل الانطلاق
> **يحل محل:** الخطة السابقة `VISUAL-IDENTITY-PLAN.md` (كانت أوسع من نطاق #56)

**المحتويات:** الجزء الأول: نص الـIssue. الجزء الثاني: الخطة التنفيذية المطابقة لحدوده.

---

# الجزء الأول — نص الـIssue

## UX-01 — توحيد الهوية البصرية للواجهات #56

### الهدف

توحيد الهوية البصرية لواجهات مسارك الحالية والقادمة بدون تغيير أي منطق أو سلوك وظيفي.

### المسؤول

**Assignee:** `ayman-albaidahi`

### المراجعة

- **Primary Reviewer:** `Alhareith`
- **Content Review عند الحاجة:** `Mulatef-Aldahia`
- **Backend/Contract Review عند الحاجة:** `Malek711`

### المطلوب

- توحيد الألوان والخطوط والعناوين.
- توحيد الأزرار والحقول والبطاقات.
- توحيد المسافات والحواف والظلال.
- توحيد حالات Loading وEmpty وSuccess وError.
- تحسين RTL وResponsive وFocus.
- تقليل تكرار الأنماط وإعادة استخدام العناصر المشتركة.

### حدود العمل

- لا تغيير في Backend أو Contracts.
- لا تعديل Models أو Migrations أو Controllers أو Routes.
- لا تغيير في Scoring أو Recommendation أو Authorization.
- لا تغيير في منطق F-03 أو `resources/js/assessment.js`.
- لا تغيير في `id` أو `data-*` أو `name` أو `value` التي يعتمد عليها JavaScript.
- لا تنفيذ لوظائف تخص F-02 أو F-04 أو F-05.
- لا تعديل نصوص معتمدة إلا بعد مراجعة ملاطف.
- لا إضافة Framework أو UI Library جديدة.

### الملفات المتوقعة

```text
resources/css/app.css
resources/views/layouts/
resources/views/components/
resources/views/
public/
```

### التحقق

```bash
npm run build
php artisan test
```

وفحص العروض: `320px` و`375px` و`768px` و`1024px`.

### معايير الإغلاق

- [ ] الهوية البصرية موحدة.
- [ ] الأزرار والحقول والبطاقات متسقة.
- [ ] الألوان والخطوط والمسافات متناسقة.
- [ ] RTL وResponsive يعملان.
- [ ] Focus واضح.
- [ ] لم يتغير أي منطق أو Contract.
- [ ] لم تتأثر F-03 أو بقية الرحلات.
- [ ] `npm run build` ناجح.
- [ ] `php artisan test` ناجح.
- [ ] Pull Request يحتوي Screenshots واضحة.

---

# الجزء الثاني — الخطة التنفيذية

## 1. ترجمة الـIssue إلى نطاق عمل

| داخل النطاق | خارج النطاق (ممنوع) |
|---|---|
| Tokens (ألوان، خطوط، مسافات، حواف، ظلال) في `app.css` | أي تغيير في Backend أو Models أو Migrations أو Controllers أو Routes |
| مكوّنات Blade مشتركة (أزرار، حقول، بطاقات، تنبيهات، حالات) | تغيير Scoring أو Recommendation أو Authorization |
| القوالب `layouts/` وصفحات الأخطاء | تعديل `assessment.js` أو منطق F-03 |
| مواءمة **الصفحات الموجودة** بصريًا فقط | تغيير `id` أو `data-*` أو `name` أو `value` التي يعتمد عليها JS |
| تحسين RTL وResponsive وFocus | بناء وظائف F-02 أو F-04 أو F-05 (مقارنة، مخططات نتائج، جداول إدارة) |
| ملفات ثابتة في `public/` (خط، شعار، فافيكون) | إضافة Framework أو UI Library جديدة |
| — | تعديل نص معتمد دون مراجعة ملاطف |

**المبدأ:** نجهّز الأساس والمكوّنات المشتركة بحيث يتبناها أصحاب F-02 وF-04 وF-05 عند بناء واجهاتهم، دون أن نبني وظائفهم نحن.

## 2. الضوابط الحاكمة (Guardrails)

1. **عقد الـJS محمي:** أي `id` أو `data-*` أو `name` أو `value` أو class يستهدفه `assessment.js` يبقى كما هو حرفيًا. الأنماط الجديدة تُضاف فوق الموجود ولا تستبدله.
2. **التحويل إلى مكوّنات لا يغيّر الـmarkup الحساس:** المكوّن يقبل `id` وسمات `data-*` وغيرها عبر `$attributes`، وعند مواءمة صفحة موجودة تُنقل السمات كما هي.
3. **النصوص لا تُلمس:** نصوص الواجهة الحالية تبقى، وأي اقتراح صياغة يُرفع لملاطف ولا يُنفَّذ قبل موافقته.
4. **لا تبعيات جديدة:** الخط ملفات محلية في `public/fonts/` (بدون حزمة npm)، والأيقونات SVG مضمّن (بدون مكتبة).
5. **لا Route جديد** دون استثناء صريح من حارث (انظر القسم 13).
6. **اختبار يدوي إلزامي لرحلة التقييم** لأن اختبارات PHP لا تغطي سلوك JavaScript.

## 3. المرحلة 0 — الجرد وسجل العقد (Audit)

**المخرج:** `docs/design/audit.md` و**سجل العقد** (Contract Register) و«لقطات قبل».

### 3.1 أوامر الجرد

```bash
# selectors وclasses التي يعتمد عليها JS
grep -nE "querySelector(All)?\(|getElementById|getElementsBy|classList|closest\(|matches\(|dataset|getAttribute\(" resources/js/assessment.js

# سمات data-* المستخدمة في الواجهات والـJS
grep -rnoE "data-[a-z0-9_-]+" resources/views resources/js | sort | uniq -c | sort -rn

# ألوان وقيم حرة مكتوبة يدويًا
grep -rnE "#[0-9a-fA-F]{3,8}\b|\[#|-\[[0-9]+(px|rem)\]" resources/views resources/css

# اختبارات تعتمد على markup أو نص
grep -rnE "assertSee|assertSeeHtml|assertSeeText|assertDontSee" tests
```

### 3.2 سجل العقد (يُملأ من ناتج الأوامر)

| العنصر (selector / id / data-*) | الملف | يعتمد عليه | يُمنع تغييره |
|---|---|---|---|
| (مثال) `#assessment-root` | `assessment/show.blade.php` | `assessment.js` | نعم |
| … | … | … | … |

### 3.3 قائمة الجرد

- [ ] قراءة `resources/css/app.css` وتوثيق الأنماط المخصصة.
- [ ] قراءة `layouts/app.blade.php` والرأس والتذييل.
- [ ] جرد أنماط الأزرار والحقول والبطاقات المكررة في كل `resources/views/`.
- [ ] تحديد إصدار Tailwind من `package.json` (v4: `@theme` في CSS، v3: `tailwind.config.js`).
- [ ] فحص الـPRs المفتوحة: هل يعدّل أيٌّ منها `app.css` أو الـlayout؟
- [ ] لقطات «قبل» لكل صفحة موجودة على 320 و375 و768 و1024.
- [ ] ما إذا كانت صفحات الأخطاء (`resources/views/errors/`) موجودة.
- [ ] تشغيل `npm run build` و`php artisan test` كخط أساس (نتيجتهما قبل أي تعديل).

## 4. الأساس العلامي

**الشخصية:** موثوق، دافئ، مُلهِم، واضح، مرشد يرافق الطالب لا محكمة تصدر أحكامًا.

**دليل النبرة (مرجع للمراجعة مع ملاطف، لا يُطبَّق على نص معتمد):**

- عربية مبسطة وجمل قصيرة، وخطاب محايد للجنس قدر الإمكان.
- النتائج «مؤشرات واستكشاف» وليست تشخيصًا.
- رسائل الخطأ تقول ما حدث وما يفعله المستخدم.

**الشعار والملفات (`public/`):** SVG أساسي، ونسخة أحادية اللون، وأيقونة مربعة، وفافيكون وApple touch icon، وصورة OG بحجم 1200×630، و`theme-color`. مساحة حماية حول الشعار بمقدار ارتفاع حرف الميم.

**الرسوم:** أسلوب واحد بـSVG بسيط بألوان الباليت، بلا صور ثقيلة، وتمثيل محترم ومحايد، و`alt` مناسب (والزخرفية `alt=""`).

## 5. نظام الألوان (مقترح أولي، يُضبط على ألوان الشعار)

### 5.1 اللون الرئيسي (Brand)

| الدرجة | القيمة | الاستخدام |
|---|---|---|
| 50 | `#eef4ff` | خلفيات ناعمة |
| 100 | `#e0eaff` | تمييز خفيف |
| 200 | `#c3d5ff` | حدود تمييز |
| 500 | `#3f6ff0` | تفاعل |
| **600** | **`#2554d6`** | **الأزرار والروابط والتركيز** |
| 700 | `#1d43ad` | hover وactive |
| 800 | `#1c3a8a` | عناوين مميزة |
| 900 | `#1b326e` | خلفيات داكنة |

**Accent (عنبري):** 50 `#fff8eb` · 100 `#ffefc7` · 500 `#f5a524` · 600 `#d97f06`. النص على العنبري `slate-900` دائمًا.

**المحايدات:** Slate (50 `#f8fafc` · 100 `#f1f5f9` · 200 `#e2e8f0` · 300 `#cbd5e1` · 600 `#475569` · 700 `#334155` · 800 `#1e293b` · 900 `#0f172a`).

### 5.2 الدلالية (Success / Warning / Danger / Info)

| الحالة | خلفية | حد | نص |
|---|---|---|---|
| نجاح | `#f0fdf4` | `#86efac` | `#15803d` |
| تحذير | `#fffbeb` | `#fcd34d` | `#b45309` |
| خطأ | `#fef2f2` | `#fca5a5` | `#b91c1c` |
| معلومة | `brand-50` | `brand-200` | `brand-800` |

### 5.3 ألوان RIASEC (محجوزة لـF-04)

نعرّف الـtokens الآن فقط ليجدها أصحاب F-04 جاهزة، ولا نبني أي واجهة تستخدمها: R `#16a34a` · I `#0891b2` · A `#9333ea` · S `#ea580c` · E `#db2777` · C `#64748b`. القاعدة: لا تُستخدم للدلالة على حالة، ولا يعتمد المعنى على اللون وحده.

### 5.4 قواعد التباين

- نص عادي ≥ 4.5:1، ونص كبير وعناصر الواجهة ≥ 3:1.
- النص الثانوي `slate-600` كحد أدنى.
- تُفحص كل تركيبة جديدة بأداة قياس قبل اعتمادها.
- الوضع الداكن مؤجل، لكن التسمية دلالية فلا يلزم إعادة بناء عند إضافته.

## 6. الخطوط والطباعة

**الخط:** IBM Plex Sans Arabic (ترخيص OFL). البديل عند الحاجة: Tajawal.

**التحميل (بدون تبعية جديدة):**

- تنزيل ملفات woff2 مرة واحدة إلى `public/fonts/` مع ملف الترخيص.
- ثلاثة أوزان فقط: 400 و500 و700، وحزمتا الحروف arabic وlatin، و`font-display: swap`.
- ميزانية الخطوط ≤ 150KB، و`preload` للوزن 400.

**السلم الطباعي (جوال أولًا):**

| العنصر | الجوال | المكتب | ارتفاع السطر | الوزن |
|---|---|---|---|---|
| Display | 32px | 48px | 1.3 | 700 |
| H1 | 28px | 36px | 1.35 | 700 |
| H2 | 24px | 30px | 1.4 | 700 |
| H3 | 20px | 24px | 1.45 | 500–700 |
| نص أساسي | 16px | 16–18px | 1.75 | 400 |
| ثانوي / تسميات | 14px | 14px | 1.65 | 400–500 |

**قواعد العربية:** لا `letter-spacing` ولا `uppercase` ولا مائل، وحقول الإدخال 16px على الأقل، وطول السطر 45–75 حرفًا، وأرقام غربية (0-9) بشكل موحد (قرار مفتوح).

## 7. المسافات والتخطيط والحركة

- **المسافات:** مقياس 4px، وبين الأقسام `py-12` جوالًا و`py-16` مكتبًا.
- **الحاوية:** `max-w-6xl mx-auto px-4 sm:px-6 lg:px-8`.
- **عرض الاختبار الأدنى:** **320px**.
- **الزوايا:** أدوات التحكم `rounded-lg`، والبطاقات `1rem`، والشارات `rounded-full`.
- **الظلال:** `shadow-card` و`shadow-pop`، منخفضة التباين.
- **الأيقونات:** SVG مضمّن عبر `x-ui.icon`، مع `rtl:rotate-180` للأسهم الاتجاهية.
- **الحركة:** انتقالات 150–200ms على اللون والشفافية والتحويل فقط، وتُلغى عند `prefers-reduced-motion`.
- **RTL:** `<html lang="ar" dir="rtl">`، وخصائص منطقية (`ms-` و`me-` و`ps-` و`pe-` و`start-` و`end-` و`text-start`) بدل `ml` و`mr` و`left` و`right`.

## 8. تنفيذ الـTokens في `app.css`

> إذا كان Tailwind v4 فالتعريفات هنا، وإذا كان v3 فتنتقل إلى `tailwind.config.js` تحت `theme.extend`.

```css
@import "tailwindcss";

@font-face {
  font-family: "IBM Plex Sans Arabic";
  src: url("/fonts/IBMPlexSansArabic-Regular.woff2") format("woff2");
  font-weight: 400; font-style: normal; font-display: swap;
}
@font-face {
  font-family: "IBM Plex Sans Arabic";
  src: url("/fonts/IBMPlexSansArabic-Medium.woff2") format("woff2");
  font-weight: 500; font-style: normal; font-display: swap;
}
@font-face {
  font-family: "IBM Plex Sans Arabic";
  src: url("/fonts/IBMPlexSansArabic-Bold.woff2") format("woff2");
  font-weight: 700; font-style: normal; font-display: swap;
}

@theme {
  --font-sans: "IBM Plex Sans Arabic", ui-sans-serif, system-ui, sans-serif;

  --color-brand-50:  #eef4ff;  --color-brand-100: #e0eaff;
  --color-brand-200: #c3d5ff;  --color-brand-500: #3f6ff0;
  --color-brand-600: #2554d6;  --color-brand-700: #1d43ad;
  --color-brand-800: #1c3a8a;  --color-brand-900: #1b326e;

  --color-accent-50:  #fff8eb; --color-accent-100: #ffefc7;
  --color-accent-500: #f5a524; --color-accent-600: #d97f06;

  --color-success-50: #f0fdf4; --color-success-700: #15803d;
  --color-warning-50: #fffbeb; --color-warning-700: #b45309;
  --color-danger-50:  #fef2f2; --color-danger-600: #dc2626; --color-danger-700: #b91c1c;

  /* محجوزة لـF-04 */
  --color-riasec-r: #16a34a; --color-riasec-i: #0891b2; --color-riasec-a: #9333ea;
  --color-riasec-s: #ea580c; --color-riasec-e: #db2777; --color-riasec-c: #64748b;

  --radius-card: 1rem;
  --shadow-card: 0 1px 2px rgb(15 23 42 / .06), 0 4px 16px rgb(15 23 42 / .06);
  --shadow-pop:  0 8px 30px rgb(15 23 42 / .14);
}

@layer base {
  html { -webkit-text-size-adjust: 100%; scroll-padding-top: 5rem; }
  body { @apply bg-slate-50 text-slate-800 font-sans leading-relaxed antialiased; }
  :focus-visible { outline: 2px solid var(--color-brand-600); outline-offset: 2px; }
  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
  }
}
```

**تنبيه:** يُحفظ محتوى `app.css` الحالي ويُدمج، ولا يُستبدل الملف قبل جرد ما يعتمد عليه.

**قاعدة:** لا قيم حرة (`bg-[#123456]`) في الـBlade، وأي قيمة جديدة تُضاف كـtoken أولًا.

## 9. المكوّنات المشتركة (Blade)

### 9.1 الاتفاقيات

- **المكان:** `resources/views/components/ui/` (`<x-ui.button>`).
- **البنية:** `@props` بقيم افتراضية، ودمج الـclasses عبر `$attributes->class([...])`، بلا `style=""`.
- **سلامة العقد:** كل مكوّن يمرر `id` و`data-*` و`name` و`value` وأي سمة أخرى عبر `$attributes` كما وصلته.
- **الحالات:** عادي، hover، focus، active، disabled، وعند الحاجة loading وerror.
- **النصوص:** لا نصوص ثابتة داخل المكوّن العام، وتأتي من الـslots أو الـprops.

### 9.2 الجرد (ضمن النطاق)

| المجموعة | المكوّنات |
|---|---|
| **P0 — الأساس** | `button` (primary / secondary / outline / ghost / danger، أحجام md وlg، loading، زر أيقونة) · `link` · `field` · `input` · `textarea` · `select` · `checkbox` · `radio` · `card` · `badge` · `alert` · `container` · `section` · `heading` · `icon` |
| **حالات الواجهة** | `flash` (نجاح/خطأ) · `empty-state` · `skeleton` · `spinner` · `alert` بأنواعه |
| **تنقل وهيكل** | `navbar` · `footer` · `dropdown` (قائمة المستخدم) · `modal` تأكيد (عند وجود حاجة فعلية في صفحة حالية) |
| **مؤجل لأصحابها** | مقارنة التخصصات وفلاتر الدليل (F-02)، مخطط RIASEC وبطاقات النتائج (F-04)، جداول الإدارة وبطاقات الإحصاء (F-05) |

المكوّنات المؤجلة تستهلك الأساس أعلاه عند بنائها، ولا نبنيها هنا.

### 9.3 أمثلة تعاقدية

**الزر:**

```blade
@props(['variant' => 'primary', 'size' => 'md'])
@php
$variants = [
  'primary'   => 'bg-brand-600 text-white hover:bg-brand-700',
  'secondary' => 'bg-brand-50 text-brand-700 hover:bg-brand-100',
  'outline'   => 'border border-slate-300 text-slate-800 hover:bg-slate-100',
  'ghost'     => 'text-slate-700 hover:bg-slate-100',
  'danger'    => 'bg-danger-600 text-white hover:bg-danger-700',
];
$sizes = ['md' => 'px-5 py-2.5 text-base', 'lg' => 'px-7 py-3.5 text-lg'];
@endphp
<button {{ $attributes->class([
  'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg font-semibold transition',
  'disabled:pointer-events-none disabled:opacity-50',
  $variants[$variant], $sizes[$size],
]) }}>{{ $slot }}</button>
```

**الحقل (يحافظ على `id` و`name` الموجودين):**

```blade
@props(['name', 'label', 'id' => null, 'hint' => null])
@php
$id = $id ?? $name;
$hasError = $errors->has($name);
@endphp
<div class="space-y-1.5">
  <label for="{{ $id }}" class="block text-sm font-medium text-slate-800">{{ $label }}</label>
  <input id="{{ $id }}" name="{{ $name }}"
    @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
    {{ $attributes->class([
      'block w-full rounded-lg border bg-white px-3.5 py-2.5 text-base text-slate-900',
      'placeholder:text-slate-500 focus:border-brand-600',
      $hasError ? 'border-danger-600' : 'border-slate-300',
    ]) }}>
  @if($hint && !$hasError)<p class="text-sm text-slate-600">{{ $hint }}</p>@endif
  @if($hasError)<p id="{{ $id }}-error" class="text-sm text-danger-700">{{ $errors->first($name) }}</p>@endif
</div>
```

عند مواءمة نموذج موجود يُمرَّر `id` الحالي صراحةً، ولا يُعتمد الافتراضي.

## 10. القوالب والحالات

- **القوالب:** توحيد `layouts/app.blade.php` (وأي قوالب أخرى في `layouts/`) على الـtokens: رأس وتذييل متجاوبان، وحاوية موحدة، ورابط «تخطَّ إلى المحتوى»، وعنوان `<title>` لكل صفحة، وفافيكون وmeta.
- **`flash`:** مكوّن واحد لرسائل الجلسة (نجاح/خطأ/تنبيه) بـ`role="status"` أو `role="alert"` حسب الحالة.
- **Loading / Empty / Error / Success:** أنماط موحدة تستخدمها كل الصفحات (`skeleton` و`spinner` و`empty-state` و`alert`).
- **صفحات الأخطاء (إن وجدت):** 404 و403 و419 و500 و503 بنفس الهوية وزر رجوع، دون تغيير النص المعتمد.

## 11. إرشادات للصفحات الحالية

| الصفحة | ما يُوحَّد (بصريًا فقط) |
|---|---|
| **مقدمة التقييم وسؤال التقييم** | البطاقات والأزرار والمسافات والتركيز وأحجام اللمس (≥ 44px) وشريط التقدم من حيث الشكل فقط. `assessment.js` و`id` و`data-*` لا تُمس. |
| **المصادقة** (دخول/تسجيل/نسيان كلمة المرور) | الحقول ورسائل التحقق والأزرار وتخطيط البطاقة المركزية. |
| **الملف الشخصي** | الحقول والبطاقات والأزرار وحالات الحفظ. |
| **دليل التخصصات** (بعد دمج F-02) | مواءمة بصرية فقط بعد الدمج وبالتنسيق مع صاحبها. |

**للمستقبل (تُذكر في وصف الـPR لأصحابها):** F-04 يستخدم ألوان RIASEC المحجوزة وقواعد التباين، وF-05 يستخدم مكوّنات الجداول والحالات مع القوالب نفسها.

## 12. الوصول والأداء وRTL/Responsive

**الوصول (WCAG 2.2 AA):**

- تركيز مرئي واضح لكل عنصر تفاعلي، وتنقل كامل بلوحة المفاتيح، وترتيب تركيز منطقي في RTL.
- لكل حقل `label` حقيقي، وأخطاء النموذج مربوطة بالحقل (`aria-describedby`).
- الألوان ليست الحامل الوحيد للمعنى، والتكبير حتى 200% دون فقدان محتوى.
- أهداف اللمس ≥ 44px للأزرار والخيارات الأساسية.

**الأداء:**

| المقياس | الهدف |
|---|---|
| CSS مضغوط | ≤ 50KB gzip |
| الخطوط | ≤ 150KB |
| طلبات خارجية وقت التشغيل | صفر (لا CDN) |
| LCP على جوال (اتصال بطيء) | ≤ 2.5 ثانية |

**Responsive:** الاختبار على **320 و375 و768 و1024**، ولا تمرير أفقي على أي عرض، والجداول العريضة تمرَّر داخل حاويتها.

## 13. الاستثناءات والأسئلة للمراجعين

**إلى Alhareith (Primary Reviewer):**

1. هل يسمح باستثناء Route لصفحة `/design-system` تعمل في بيئة `local` فقط؟ البديل: `docs/design/components.md` مع لقطات.
2. هل تقبل إضافة اختبارات Blade للمكوّنات في `tests/`؟ (غير مذكورة في «الملفات المتوقعة».)
3. هل يفضَّل PR واحد بـcommits مرتبة حسب المراحل، أم تقسيمه؟
4. تأكيد الخط المحلي في `public/fonts/` بدل حزمة npm.

**إلى Mulatef-Aldahia:** لن يُغيَّر أي نص معتمد، وأي اقتراح صياغة سيُرفع إليه للموافقة.

**إلى Malek711:** لا تغيير في Contracts، والمراجعة عند الحاجة فقط (لتأكيد عدم المساس بأسماء الحقول).

## 14. مراحل التنفيذ

> التقدير النسبي لمطوّر واحد: **S** ≈ نصف يوم، **M** ≈ 1–2 يوم، **L** ≈ 3 أيام أو أكثر.

| # | المرحلة | الأعمال | المخرجات | الحجم |
|---|---|---|---|---|
| 0 | الجرد | القسم 3، وخط أساس البناء والاختبارات، ولقطات قبل | `audit.md` وسجل العقد | S |
| 1 | القرارات | اعتماد الباليت والخط والأرقام والشعار والأيقونات | قرارات موثقة وملفات `public/` | S |
| 2 | الأساس | خط محلي، و`@theme`، والأنماط الأساسية، وفافيكون وmeta | `app.css` جديد وبناء ناجح | M |
| 3 | المكوّنات | مكوّنات P0 وحالات الواجهة | `components/ui/*` | L |
| 4 | القوالب | `layouts/` وnavbar وfooter وflash وصفحات الأخطاء | قوالب موحدة | M |
| 5 | المواءمة | صفحتا التقييم، المصادقة، الملف الشخصي، وواجهات F-02 بعد دمجها | صفحات موحدة | M |
| 6 | التحقق والتوثيق | اختبار يدوي لرحلة التقييم، الوصول، الأداء، لقطات بعد، توثيق | تقرير QA ووصف PR | M |

**ترتيب العمل:** 0 ← 1 ← 2 ← 3 ← 4 ← 5 ← 6، وأي مرحلة لا تنتقل قبل نجاح `npm run build` و`php artisan test`.

**الفرع:** `feature/ux-01-visual-identity` (أو الفرع الذي أُنشئ سابقًا، ويُعاد تسميته عبر `git branch -m` إن رغب الفريق). ويُتبع نمط الـcommits المعتمد، وكل commit يشير إلى `#56`.

## 15. التحقق

| النوع | الطريقة |
|---|---|
| البناء | `npm run build` |
| الاختبارات | `php artisan test` |
| **رحلة التقييم (يدوي)** | بدء التقييم، والإجابة، والحفظ، والرجوع، والاستئناف، والإكمال، على جوال وعلى مكتب |
| العروض | 320 و375 و768 و1024، دون تمرير أفقي |
| المتصفحات | Chrome وFirefox وEdge، وChrome على Android، وSafari على iOS |
| الوصول | لوحة مفاتيح، وتكبير 200%، وأداة تباين، وLighthouse |
| العقد | مقارنة `id` و`data-*` و`name` و`value` قبل/بعد على كل صفحة تأثرت (سجل العقد) |
| المحتوى | نصوص طويلة وأسماء تخصصات طويلة، والتفاف الأسطر، والأرقام |

## 16. لقطات الـPR (Screenshots)

جدول تغطية يُملأ ويُرفق في وصف الـPR (قبل/بعد لكل خانة):

| الصفحة | 320 | 375 | 768 | 1024 |
|---|---|---|---|---|
| الرئيسية | ☐ | ☐ | ☐ | ☐ |
| الدخول / التسجيل | ☐ | ☐ | ☐ | ☐ |
| مقدمة التقييم | ☐ | ☐ | ☐ | ☐ |
| سؤال التقييم | ☐ | ☐ | ☐ | ☐ |
| الملف الشخصي | ☐ | ☐ | ☐ | ☐ |
| حالات: خطأ / فراغ / نجاح / تحميل | ☐ | ☐ | ☐ | ☐ |
| صفحة خطأ (404 مثلًا) | ☐ | ☐ | ☐ | ☐ |

## 17. المخاطر والتخفيف

| الخطر | التخفيف |
|---|---|
| كسر `assessment.js` بحذف class أو تغيير `id` أو `data-*` | سجل العقد، وعدم حذف أي class يستهدفه JS، واختبار يدوي كامل للرحلة |
| اختبارات تعتمد على markup أو نص | فحص `assertSee*` في المرحلة 0، وعدم تغيير نص أو بنية يعتمد عليها اختبار |
| تعارض مع فروع F-02 وF-04 وF-05 وPRs مفتوحة | تنسيق قبل لمس `app.css` والـlayout، ودمج مبكر للأساس |
| بطء الخطوط والـCSS على اتصال ضعيف | ميزانية أداء وخط محلي بثلاثة أوزان |
| أخطاء RTL | خصائص منطقية دائمًا، ومراجعة كل صفحة على RTL |
| توسع النطاق نحو وظائف F-02/F-04/F-05 | الالتزام بقسم «خارج النطاق»، وأي فكرة إضافية تُرفع لحارث |
| اختلاف إصدار Tailwind عن المتوقع | التحقق من `package.json` في المرحلة 0 |

## 18. تعريف الاكتمال (مطابق لمعايير الإغلاق)

- [ ] **الهوية البصرية موحدة:** Tokens وخط محلي وشعار وفافيكون مطبقة.
- [ ] **الأزرار والحقول والبطاقات متسقة:** كلها عبر `x-ui.*`، بلا قيم حرة.
- [ ] **الألوان والخطوط والمسافات متناسقة:** من الـtokens فقط.
- [ ] **RTL وResponsive يعملان** على 320 و375 و768 و1024.
- [ ] **Focus واضح** على كل عنصر تفاعلي.
- [ ] **لم يتغير أي منطق أو Contract:** سجل العقد مطابق قبل/بعد.
- [ ] **لم تتأثر F-03 أو بقية الرحلات:** اختبار يدوي كامل لرحلة التقييم.
- [ ] `npm run build` ناجح.
- [ ] `php artisan test` ناجح.
- [ ] **PR يحتوي Screenshots واضحة** حسب الجدول في القسم 16.
- [ ] توثيق مختصر في `docs/design/` وتسجيل الاستخدام المؤثر للذكاء الاصطناعي في `AI_Log.md`.

## 19. القرارات المفتوحة

| القرار | التوصية الافتراضية | من يقرر |
|---|---|---|
| اللون الرئيسي | اللون الغالب في الشعار كـ`brand-600` ثم توليد الدرجات | Assignee + Alhareith |
| الخط | IBM Plex Sans Arabic محليًا | Alhareith |
| الأرقام | غربية (0-9) موحّدة | الفريق |
| الأيقونات | SVG مضمّن بدون مكتبة | Assignee |
| `/design-system` | Route محلي فقط بموافقة صريحة، وإلا `components.md` | Alhareith |
| اختبارات المكوّنات | تُضاف بموافقة Alhareith | Alhareith |
| وضع داكن | مؤجل | الفريق |

---

## ملحق: مسودة رسالة إلى Alhareith قبل البدء

> السلام عليكم حارث، قبل ما أبدأ في UX-01 (#56) أحتاج تأكيد أربع نقاط:
> 1) هل تسمح بصفحة `/design-system` كـRoute لبيئة `local` فقط؟ وإلا سأوثق المكوّنات في `docs/design/components.md` مع لقطات.
> 2) هل أضيف اختبارات Blade للمكوّنات في `tests/`؟
> 3) أفضّل PR واحدًا بـcommits مرتبة حسب المراحل، هل يناسبك أم تفضّل تقسيمه؟
> 4) الخط سيكون ملفات woff2 محلية في `public/fonts/` (ترخيص OFL) بدون حزمة npm جديدة.
> وسأبدأ بالجرد وأرفع لقطات «قبل» ثم سجل العقد قبل أي تعديل.
