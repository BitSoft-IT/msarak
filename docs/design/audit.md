# UX-01 — المرحلة 0: الجرد وسجل العقد

> **Issue:** #56 — توحيد الهوية البصرية للواجهات
> **الفرع:** `feature/ux-01-visual-identity`
> **تاريخ الجرد:** 2026-09-24
> **المرجع:** `docs/design/UX-01-VISUAL-IDENTITY.md` (القسم 3)
> **طبيعة الملف:** قراءة فقط — لا يمثل أي تعديل على الكود.

---

## 0. خط الأساس (قبل أي تعديل)

| الفحص | النتيجة |
|---|---|
| `npm run build` | ✅ ناجح — CSS ‏69.65KB (gzip ‏11.74KB)، JS ‏73.15KB (gzip ‏25.34KB)، 61 وحدة |
| `php artisan test` | ✅ ناجح — كل الاختبارات خضراء، ‏1491 تأكيدًا |
| ميزانية CSS المستهدفة (§12: ≤ 50KB gzip) | ✅ حاليًا 11.74KB gzip — متسع كبير |
| لقطات «قبل» على 320/375/768/1024 | ⏳ مؤجلة إلى ما قبل المرحلة 5 (تُسجَّل مع وصف الـPR وفق §16) |

---

## 1. الأساس البيئي

| البند | الحالة الحالية | ملف/سطر |
|---|---|---|
| إصدار Tailwind | **v4** (`tailwindcss ^4.0.0` + `@tailwindcss/vite ^4.0.0`) — إعداد CSS-first عبر `@theme`؛ **لا وجود** لـ`tailwind.config.js` | `package.json` |
| التوكنز الحالية | 3 ألوان brand فقط وهي **مؤقتة (indigo)**: `--color-brand-50: #eef2ff` · `600: #4f46e5` · `700: #4338ca` — لا تطابق باليت الوثيقة (`brand-600: #2554d6`) | `resources/css/app.css:12-14` |
| الخط | `IBM Plex Sans Arabic` **من Google Fonts CDN** — يخالف قاعدة «صفر طلبات خارجية» (§12)؛ `@theme` يشير إليه أيضًا بلا ملفات محلية | `resources/views/layouts/app.blade.php:10-12` · `app.css:9` |
| أصول الهوية | `public/` فيه `favicon.ico` فقط — **لا** `public/fonts/` ولا شعار SVG ولا صورة OG | `public/` |
| صفحات الأخطاء | `resources/views/errors/` **غير موجودة** (الـ404 يُعالَج بـrender مخصص يعيد JSON لمسارات assessment) | — |
| فروع/PRs مفتوحة تلمس `app.css` أو `layouts/` | لا يوجد — فرع F-02 دُمج في main ‏(#54)، وC-04 دُمج (#55)؛ الفرع الحالي مبني على أحدث origin/main (يتضمن F-03 #53) | git |

### ملاحظات على `app.css` الحالية (تُدمَج ولا تُستبدل وفق §8)
- مكوّنات معرّفة: `.container-page` (max-w-**5xl**، بينما توصي الوثيقة بـmax-w-6xl — قرار معلق للمرحلة 2)، `.btn/.btn-primary/.btn-secondary`، `.field-label/.field-input`، `.alert/.alert-success/.alert-error`، `.ltr-text`، `.skip-link`، عائلة `.auth-*` (shell/card/panel/panel-icon)، `.field-wrap/.field-toggle/.field-input-with-toggle`.
- قسم الحركة (`prefers-reduced-motion: no-preference`) يعرف تنقّلات لـ`.option-card` و`.rating-btn` — **كلاسا DOM يولّدهما `assessment.js`**: حذفهما من CSS يفقد الخيارات والتقييمات تحوّلاتها (جزء من العقد).
- أنماط `animate-in fade-in zoom-in-95 duration-200` المستخدمة في مودالات `show.blade.php` غير معرّفة في المشروع (Tailwind v4 لا يوفّرها) — **كلاسات ميتة** تُصحَّح في المرحلة 2 أو تُستبدل بـ`transition` قياسي.

---

## 2. سجل العقد (Contract Register)

**القاعدة الحاكمة:** كل عنصر في هذا الجدول يبقى حرفيًا كما هو خلال كل المراحل. تُضاف الأنماط فوقه ولا تُستبدل تراكيبه. عند تحويل صفحة إلى مكوّن `x-ui.*` تُنقل السمات كما هي عبر `$attributes`.

### 2.1 `assessment.js` ↔ `resources/views/assessment/show.blade.php`

| العنصر | موضعه في views | كيف يستهدفه JS |
|---|---|---|
| `#assessment-app` بسمات `data-session-id` · `data-complete-url` · `data-save-base-url` · `data-session-url` | show.blade.php:6-11 | `dataset.sessionId/completeUrl/saveBaseUrl/sessionUrl` (51-53, 173)؛ التهيئة عبر `getElementById('assessment-app')` (729) |
| `meta[name="csrf-token"]` | layouts/app.blade.php:6 | ترويسة `X-CSRF-TOKEN` (54) |
| `#assessment-initial-data` (`<script type="application/json">`) | show:14 | `JSON.parse(textContent)` (156-159) — بقاؤه إلزامي لتفادي وميض التحميل |
| `#current-position-num` | show:55 | `textContent` (74, 260) |
| `#total-questions-num` | show:55 | `textContent` (75, 261) |
| `#processed-count-num` | show:58 | `textContent` (76, 589) |
| `#progress-bar-fill` | show:65 | `style.width` (77, 591) — يُسمح بتغيير أصنافه فقط |
| `#progress-bar-container` | show:64 | `setAttribute('aria-valuenow')` (78, 592) |
| `#scenario-badge` | show:74 | مرجع (80) |
| `#badge-num` | show:76 | `textContent` (81, 259) |
| `#scenario-text` | show:83 | `textContent` (82, 262) |
| `#options-container` | show:120 | `innerHTML` — حاوية الحقن الديناميكي (83, 270) |
| `#none-fit-radio` · `#none-fit-card` | show:132-133 | `change` listener + `checked` + **استبدال `className` كاملًا** للبطاقة (85-86, 265, 374-378) |
| `#cannot-judge-radio` · `#cannot-judge-card` | show:148 | نفس النمط (87-88, 266) |
| `input[name="response_state"]` بقيمتي `none_selected` / `unable_to_judge` | show:133, 149 | منطق الحفظ والتبديل؛ `name` و`value` جزء من عقد الـbackend أيضًا |
| `#save-status-icon` · `#save-status-text` | show:79-80 | `innerHTML`/`textContent` لحالات الحفظ (90-91, 539-565) |
| `#error-alert` · `#error-alert-message` | show:92, 99 | إظهار/إخفاء بـ`classList hidden` + نص (93-94, 570-575) |
| `#retry-save-btn` | show:102 | `click` → `flushSave` (95, 122) |
| `#prev-btn` · `#next-btn` · `#complete-btn` | show:167, 175, 182 | `click` + `disabled` + `hidden` toggling (97-99, 115-117, 625-639) — **الإخفاء يتم بـ`.hidden` وحده**: يجب أن تبقى قاعدة `!important`/الترتيب كافية لإخفاء `display:inline-flex` |
| `#questions-nav-grid` | show:203 | `innerHTML` وبناء أزرار ديناميكي (100, 596-621) |
| `#conflict-modal` + `#conflict-keep-btn` + `#conflict-review-btn` | show:209-231 | `hidden` toggling + `click` + فحص `classList.contains('hidden')` للمتن (103-105, 125-126, 146, 443-447) |
| `#completion-modal` + `#completion-confirm-btn` + `#completion-cancel-btn` + `#completion-modal-error` | show:234-258 | نفس النمط + `textContent` («جارٍ الحفظ...» / «إكمال التقييم») (108-111, 129-130, 672-712) |
| أصناف يضيفها JS ديناميكيًا وتُبنى فوقها تنقّلات `app.css`: `option-card` · `rating-btn` | مولّدة (assessment.js:279, 344) | يجب بقاء كلاسي CSS لهما في `app.css:92-98` |
| `input[name="primary_option"][value="{option_id}"]` بـ`id="opt-radio-{option_id}"` + `label[for]` | مولّدة (300-310) | ربط radio/label — لا يُعاد تسمية الصيغة |
| `#rating-scale-legend` (`<template>`) مع `data-rating` ×5 | show:19-40 | لا يستهدفه JS حاليًا، لكن نصوصه مشمولة باختبار `assertSee('يشبهني جدًا')` |
| ترتيب `flex hidden` في المودالات | show:209, 234 | `hidden` يتغلب على `flex` في Tailwind v4 (قاعدة `!important`) — **لا يجوز فصلهما ولا استبدال `hidden` بـ`invisible`** |

### 2.2 `auth.js` ↔ صفحات `resources/views/auth/`

| العنصر | موضعه | كيف يستهدفه JS |
|---|---|---|
| `[data-password-toggle]` + `data-target` (يجب أن يساوي `id` الحقل حرفيًا) | login:40 · register:50, 74 · reset:37, 61 | `closest` (15) → `getElementById(button.dataset.target)` (2) |
| `[data-icon-show]` / `[data-icon-hide]` داخل الزر نفسه | login:42, 46 · register:52, 56, 76, 80 · reset:39, 43, 63, 67 | `classList.toggle('hidden', …)` (10-11) |
| `input#password` · `input#password_confirmation` (+بدّل `type`) | نفس الصفحات | تبديل `type` + `aria-pressed` + `aria-label` (5-8) — `aria-label` يتغير ديناميكيًا («إظهار/إخفاء كلمة المرور») فلا يُكتب النص الجديد يدويًا |
| `.auth-form` | login:14 · register:10 · forgot:15 · reset:10 | `closest` على submit (20) |
| `button[type="submit"]` داخل `.auth-form` | كل النماذج | استبدال `textContent` بـ«جارٍ المعالجة...» + `disabled` + `aria-busy` (23-29) — الزر يجب أن يبقى `button[type=submit]` مع نص قابل للاستبدال (بلا أيقونة داخله تفترضها اختبارات؟ لا — نصوصه حرة لأن JS يستبدل textContent بالكامل) |
| حقول `name="email"` · `name="password"` · `name="password_confirmation"` · `input[name="token"]` مخفي | نماذج auth | عقد backend (`app/Http/Requests`) — لا يمسّه التنسيق |

### 2.3 `specializations.js` ↔ صفحات `specializations/`

| العنصر | موضعه | كيف يستهدفه JS |
|---|---|---|
| `#compare-bar` | index.blade.php:14 | `classList.add/remove('hidden')` (18-26) |
| `#compare-bar-names` | index:16 | `textContent` (27) |
| `#compare-bar-link` | index:19 | `link.href` + `classList` لـ`pointer-events-none` و`opacity-50` (30-35) — يجب بقاؤها كلاسات utility صالحة |
| `#compare-bar-clear` | index:22 | `event.target.id === 'compare-bar-clear'` (66) — **الزر نفسه يجب أن يبقى `id` على العنصر المُلاحَظ مباشرة** (لا على غلاف داخلي) لأن الفحص على `event.target` |
| `.compare-toggle` + `data-id` + `data-name` (+`data-redirect` في صفحة التفاصيل) | index:40-43 · show:59-63 | `closest('.compare-toggle')` + `dataset` (40-43) |
| `sessionStorage['specializations-compare']` | — | بنية `{id, name}` — لا تتأثر بالـCSS |

### 2.4 عقد نصّي خارج JS (اختبارات)

| العنصر | المصدر |
|---|---|
| `name="_token"` (أي `<input type="hidden" name="_token">`) | `tests/Feature/Auth/SessionSecurityTest.php:173` — كل نموذج `@csrf` يجب أن يبقى يولّده |
| نصوص «بدء التقييم» / «متابعة التقييم» / «طريقة الإجابة» / «لديك تقييم غير مكتمل» / «استكشاف ميولك» / «إكمال التقييم» / «لا يشبهني أي من هذه التصرفات» / «لا أستطيع الحكم على هذا الموقف» / «يشبهني جدًا» | `tests/Feature/AssessmentUiTest.php:90-147` — موجودة في `intro.blade.php` و`show.blade.php` (بما فيها قالب `#rating-scale-legend`)؛ ممنوع حذفها أو نقلها لمكوّن بلا slot نصّي يبقى في HTML المستجاب |
| غياب `riasec_code` في HTML | `AssessmentUiTest.php:150-151` — لا يضيف أي تنسيق لاحقًا بيانات تصحيح إلى markup |

---

## 3. الأنماط المكررة والقيم الحرة

### 3.1 الأزرار — 5 أنظمة متوازية يجب توحيدها
| النظام | أمثلة |
|---|---|
| `.btn` + `.btn-primary` | auth (login:56, register:87, forgot:32, reset:74) · intro:283 · show:175, 223, 226, 251 |
| `.btn` + `.btn-secondary` | show:167, 223, 251 |
| أخضر مخصص inline خارج النظام | `#complete-btn` (show:182): `btn bg-green-700 text-white hover:bg-green-800 …` · `#completion-confirm-btn` (show:254) — **نفس التكرار** |
| أزرار بيضا/مسلّحة على تدرّج | intro:98 («متابعة التقييم»: `bg-white … text-brand-700 rounded-xl`) |
| أزرار نصية/روابط مزخرفة | `#retry-save-btn` (show:102: `bg-red-600 rounded-lg …`) · `#compare-bar-link` (index:19: `bg-brand-600 rounded-lg`) · `#compare-bar-clear` (index:22) · «رجوع» (show/index specializations:7) |

### 3.2 الحقول
- نظام `.field-label/.field-input` + `.ltr-text` + عائلة `.field-*` للتوجل — مستخدم في auth فقط، وهو الأقرب للمعيار؛ صفحات التقييم تستخدم inline (radios في show:133, 149).
- رسائل أخطاء الحقول مكررة inline: `mt-1 text-sm text-red-700` ×8 في auth (بدل توكن `text-danger-700` مقترح).

### 3.3 البطاقات
- النمط الأكثر تكرارًا: `rounded-2xl border border-slate-200 bg-white p-5|p-6 shadow-sm` — show:43, 70, 193 · intro:214, 229 (نسخ شبه متطابقة مع فروق padding فقط).
- Hero `rounded-3xl border … shadow-sm` مع تدرّج — intro:9, 110, 268.
- بطاقات التخصصات `rounded-2xl border border-slate-200 p-6` **بلا خلفية** — index:30 · compare:17.
- `.auth-card` — معرّف في CSS (نمط جيد يُحتذى).
- شارات صغيرة مكررة: `inline-flex … rounded-full … px-… text-xs font-bold` — intro:16, 215, 230 · show:74.

### 3.4 التنبيهات والحالات
- `.alert .alert-success` مستخدم مرتين فقط (login:11, forgot:12) رغم وجود `.alert-error` معرفًا وغير مستخدم.
- `#error-alert` يعيد تعريف alert أحمر inline (show:92: `border-s-4 border-red-600 bg-red-50 …`) بدل `.alert-error`.
- تنبيه ذهبي مخصص intro:250 (gradient amber)، تنبيه استئناف intro:80 (gradient brand→indigo) — أنماط حالة متفرقة.
- لا يوجد أي مكوّن Loading/Empty موحّد (spinner/skeleton) — «جارٍ تحميل الموقف…» نص ثابت فقط.

### 3.5 الألوان والقيم الحرة خارج التوكنز
| النوع | المواضع |
|---|---|
| هكسات في CSS | `app.css:12-14` (brand indigo مؤقتة) — يجب استبدالها بباليت الوثيقة §5.1 |
| `indigo-*` مباشرة (خارج brand) | intro:9 (`to-indigo-50/30`), 12 (`bg-indigo-500/10`), 80 (`to-indigo-700`) |
| `emerald/teal/amber/rose` مباشرة | intro:148-201 (سلم المعاينة) · show:198-203 (legend الخريطة) · مولّد في assessment.js (RATING_LEVELS — **محظور اللمس، §12**؛ هذه النسخ الثابتة منها في intro/show فقط) |
| `green`/`red` دلتًا على النجاح/الخطأ خارج `.alert` | show:92, 102 (`red-600/700`) · show:182, 254 (`green-700/800`) · auth (`red-700` ×8) — تُرحَّل إلى دلاليات success/danger في المرحلة 2 |
| قيم arbitrary في blade | `min-w-[640px]` (compare:34) · `text-[10px]` ×5 (intro:156-200) · `text-[11px]` (intro:242) — تخالف قاعدة «لا قيم حرة» (§8)؛ تُستبدل بسلم طبعي tokens في المرحلة 2 |

### 3.6 ملاحظات RTL/Responsive/تركيز
- `pr-5` في القوائم (specializations/show:28, 40, 51 · compare:51) — يخالف قاعدة الخصائص المنطقية (§7): الصواب `pe-5`/`ps-5`؛ يُصحَّح في المرحلة 5 (لا JS يستهدفها).
- رأس الـlayout لا ينطوي في عرض 320px بشكل كامل (روابط nav بـ`flex-wrap` — مقبول، يُتحقق باللقطات).
- `:focus-visible` معرّف عام (app.css:22-24) ✅ — لكن `#options-container` المولّد يعتمد على radio مخفي `sr-only`: التركيز البصري غير مضمون؛ يعالج في المرحلة 3 (حلقة focus على البطاقة عبر `:has` أو class).
- أهداف اللمس: `.btn` بـ`min-h-11` ✅؛ راديوهات `#none-fit-radio` بحجم `h-4 w-4` لكن الـlabel كامل `#none-fit-card` قابل للنقر ✅.

---

## 4. الاختبارات المعتمدة على markup أو نص (من `grep assertSee* tests`)

| الملف | ما يفرضه | الأثر على التنفيذ |
|---|---|---|
| `tests/Feature/AssessmentUiTest.php:90-151` | نصوص intro (9 عناصر نصية) + نصوص show + عدم تسريب `riasec_code` | لا يُعاد تسمية أو حذف أي نص ظهر أعلاه؛ النصوص في `#rating-scale-legend` و`#assessment-initial-data` تُعد «ظاهرة» لأنها في HTML المستجاب |
| `tests/Feature/Auth/SessionSecurityTest.php:173` | `name="_token"` موجود في صفحة login | كل نموذج يبقى `@csrf` |
| بقية الاختبارات | JSON/منطق/قواعد بيانات | لا تتأثر بتغيير CSS/markup بصري |

---

## 5. خلاصة المرحلة 0 وجاهزية المرحلة 1

1. الأساس واضح: **Tailwind v4 بـ`@theme` CSS-first** — كل الباليت والمسافات والظلال تُعرَّف في `app.css` (القسم 8 من الوثيقة جاهز حرفيًا للتطبيق).
2. سجل العقد أُغلق: ‏**~61 نقطة ربط** بين JS وBlade (معرّفات + data-* + أصناف مولّدة) — كلها تُحمى كما هي.
3. أهم 5 مخالفات يجب معالجتها في المراحل القادمة: (أ) CDN الخط (ب) باليت indigo المؤقتة خارج الوثيقة (ج) تكرار نمط البطاقات/الأزرار بـinline (د) ألوان دلالية مباشرة بلا توكنز (هـ) `pr-5` وكلاسات `animate-in` الميتة.
4. **قراران مطلوبان قبل المرحلة 2:** عرض الحاوية (`container-page`: إبقاء 5xl أم ترقية إلى 6xl وفق §7؟) ونسخ الخط المحلي woff2 (لا يمكن تنزيله ضمن هذه المهمة بلا شبكة؟ — يُقترح تحميله يدويًا من مصدر OFL الرسمي إلى `public/fonts/` في المرحلة 1).
5. بنود متبقية: لقطات «قبل» تُلتقط قبل المرحلة 5 لكل صفحة على 320/375/768/1024.

**المرحلة التالية (1 — القرارات):** تثبيت الباليت النهائية على ألوان الشعار، اعتماد IBM Plex المحلي بثلاثة أوزان، اعتماد الأرقام الغربية، ثم تجهيز `public/fonts/` + الشعار/الفافيكون. لا تعديل على الكود فيها قبل موافقة المراجع (أسئلة §13 الموجهة للحارث).
