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

---

## 6. تقرير ما بعد التنفيذ (2026-09-24)

> **ملاحظة:** الشعار والفافيكون وOG **مؤجلة بقرار** (المرحلة 1 جزئيًا)؛ توقيت `theme-color` حاليًا `#4f46e5` من brand-600 المؤقت المعتمد من أيمن، يُحدَّث عند اعتماد الشعار.

### ما نُفّذ
| المرحلة | العمل | الملفات |
|---|---|---|
| 2 | توكنز كاملة في `@theme` (brand 50–900، accent، success/warning/danger، radius-card، shadow-card/pop)؛ `@import './fonts.css'`؛ base (focus-visible، reduced-motion مع !important الوحيد)؛ مكوّنات `@layer components` بالـtokens؛ `.btn-success` جديد؛ preload للخط المحلي بدل CDN | `resources/css/app.css` · `layouts/app.blade.php` |
| 3 | 19 مكوّنًا في `components/ui/` (button/link/field/input/textarea/select/checkbox/radio/card/badge/alert/container/section/heading/icon + flash/empty-state/skeleton/spinner) — كلها `$attributes`-safe، لا نصوص ثابتة، لا استهلاك بعد | `resources/views/components/ui/*` |
| 4 | `flash` عالمي في layout (مع إصلاح الدلالة: status→success بدل info)؛ إزالة تكراره من login/forgot؛ 5 صفحات أخطاء `errors/{403,404,419,500,503}.blade.php` بهوية موحّدة وrole=main وزر رجوع؛ header sticky + theme-color | layouts + auth + errors/* |
| 5 | مواءمة بصرية فقط: intro (indigo→brand، `text-[10px]/[11px]`→`text-xs`)؛ show (زرّا الإكمال→`.btn-success`، error-alert/retry→danger)؛ auth (`text-red-700`→`text-danger-700` ×8)؛ specializations (`pr-5`→`ps-5`، `min-w-[640px]`→`min-w-160` (640px)، `text-right`→`text-start`)؛ py-8→py-12/lg:py-16 في main | assessment/intro+show، auth×4، specializations×2 |

### التحقق بعد التنفيذ (كلها مقابل خط الأساس §0)
| الفحص | النتيجة |
|---|---|
| `npm run build` | ✅ ناجح — CSS **78.95KB (gzip 13.27KB)** ← من 69.65KB (11.74KB)؛ لا يزال بعيدًا عن سقف §12 (50KB)؛ JS بلا تغيير (73.15KB — لم يُمسّ) |
| `php artisan test` | ✅ **282 / 1491 كاملة** بعد كل مرحلة |
| أصناف JS وقت التشغيل في المبنى | ✅ emerald/teal/amber/rose كلها مولّدة (audit §2.1)؛ `.option-card`/`.rating-btn` في مكانها الحرفي خارج الـlayers |
| عقد markup الحيّ (curl لرحلة كاملة: login→intro→session→show) | ✅ كل المعرّفات الاثنا عشر المحرزة هنا موجودة: `#assessment-app` بالـ4 data-*، `#assessment-initial-data`، `#options-container`، `#none-fit-radio`، `#complete-btn`/`#completion-confirm-btn` بـ`btn btn-success` + `hidden` محفوظ، `#rating-scale-legend`، صفر `riasec` في HTML، contract `name="_token"` سليم |
| 404 الحيّ | ✅ صفحة `errors/404` تُعرض برأس موحد (JSON عقد bootstrap لا يُمس) |
| لا قيم حرة (`[...]`) / ألوان indigo/green/red خام / خصائص فيزيائية ml-mr-pr-pl | ✅ مسح grep شامل نظيف |
| `!important` في `app.css` | ✅ اثنان فقط — داخل بلوك reduced-motion المسموح (استثناء §8)؛ قاعدة `.hidden` الخاصة بـTailwind في المبنى لا تُمس (تحمي `flex hidden`) |

### بقية §18 قبل الإغلاق
- [ ] لقطات PR (§16) على 320/375/768/1024 — لا متصفح آلي في البيئة الحالية؛ تُلتقط يدويًا عند المراجعة.
- [ ] اختبار يدوي لرحلة التقييم في متصفح حقيقي (§2 فقرة 6 — سلوك assessment.js غير مغطى بـPHP).
- [ ] الشعار/الفافيكون/OG (مرحلة 1 مؤجلة) وضبط `theme-color` عليها لاحقًا.
- [ ] فحص تباين تركيبي نهائي (§5.4) عند اعتماد اللون من الشعار.
- [ ] تسجيل AI_Log.md عند إنشاء الـPR (يعتمد على صلاحية التعديل — لم يُنفَّذ ضمن حدود ux-01).

---

## 7. جولة «اللمسة الفاخرة» (2026-09-24) — الصفحات الحالية فقط

بطلب من أيمن: رفع المستوى البصري مع ثبات القيود (عقد JS، نصوص معتمدة، لا JS/backend، لا صفحات جديدة).

### القرارات التصميمية (كلها في `app.css` + Blade، لا توكنز جديدة عدا reuse)
- **محرك الحركة في CSS بدل تكرار الـclasses:** `.btn` أصببت transition-all + لمسة ارتقاء hover `-translate-y-0.5`؛ `.btn-primary/.btn-success` بظلال ملونة `shadow-brand-600/25→/30`؛ `.field-input` بحلقة تركيز `ring-4 ring-brand-600/15` (وإلغاء outline المكرر على الحقول فقط).
- **طبقة بطاقات مشتركة:** `.card-surface` (rounded-card + shadow-card) و`.card-lift` (ترقية حدود brand + shadow-pop) — استُهلكت في التقييم والتخصصات والرئيسية بدل inline المتكرر.
- **الهيرو:** الرئيسية أعيد تركيبها (hero بتدرّج brand + هالات blur + شارة + بطاقات وصول) — **بإعادة استخدام نصوص معتمدة فقط** من intro/auth/specializations، بلا صياغات جديدة (مع تصويب «مَسَارك»→«مسارك» لتطابق الـtitle في الـlayout).
- **الرأس:** blur شفاف `bg-white/85 backdrop-blur-md` + شعار نصي بمربع brand «م» + hover pill للروابط.
- **auth-panel:** تدرّج أعمق 700→600→800 + هالات blur (accent في إحداها) + ring للأيقونة.
- **تخصصات:** البطاقات card-lift، شريط المقارنة تدرّجي بظل (وترتيبه `flex hidden` مطابق للمودالات — `hidden` يتغلب)، التفاصيل/المقارنة موحّدة بالبطاقات وmarker ملوّن والجدول بـzebra وعناوين brand-50/60.
- **التقييم:** رأس وبطاقة الموقف وnav الخريطة → `card-surface`؛ شريط التقدم بتدرّج `from-brand-500 to-brand-700` مع توهج؛ **بلا hover-lift على بطاقة الأسئلة** (تجنب إزاحة المحتوى أثناء الإجابة).

### التحقق بعد الجولة (كلها مقابل ما قبلها)
| الفحص | النتيجة |
|---|---|
| `npm run build` | ✅ CSS 90.42KB (gzip **14.29KB** — أقل من ثلث سقف §12)؛ JS لم يُمس (73.15KB) |
| `php artisan test` | ✅ **282 / 1491 كاملة** |
| رحلة حية (login→intro→session→show) بـcurl | ✅ العقد الـ21 كلها 1/1: `#assessment-app` والـ4 data-*، المعرّفات كلها، `btn btn-success` ×2، `flex hidden` ×2، صفر riasec |
| التخصصات حية | ✅ `#compare-bar*` ×4، `compare-toggle` ×10 مع data-id/name/redirect، صفحة تفاصيل 200، مقارنة (معرّفان مختلفان) 200 — والـ404 عند تساويهما سلوك موجود غير معدّل |
| الرئيسية حية | ✅ هيرو + h1 موجودان، والروابط تتبدل حسب auth/guest |

### ما لم يُمس (تأكيد)
`resources/js/**` (بما فيه assessment.js وfonts.css)، أي id/name/value/data-*، أي نص عربي معتمد (النصوص الجديدة في home كلها مستعارة حرفيًا من صفحات أخرى)، routes/tests/backend، `public/` (عدا build الناتج المتجاهَل).

### 7.1 جولة «لمسة إبداعية» لدليل التخصصات
- **لغة «المعرض»:** كل تخصص يُعرَّف بحرف-شعار (monogram) متدرّج `brand-500→700` + شريط علوي رفيع بتدرّج يضيء عند الـhover + ترقيم لطيف — بنفس لغة هيرو الرئيسية (تدرّجات + هالات blur + accent)، لا ألوان خارج التوكنز.
- **index:** هيرو دليل + بطاقات معرض؛ **show:** ترويسة ببطاقة فيها monogram أكبر (h-14) وهالات؛ **compare:** بطاقتا المقارنة بـmonogram + وسم «الأول/الثاني» (كلمتان محايدتان جديدتان خاضعتان لمراجعة ملاطف عند الـPR).
- التحقق: build ✅ 14.52KB gzip؛ test ✅ 282/1491؛ حيًا: compare-toggle ×10 + btn-secondary ×10 + plus ×10 + data-id/name ×10، والتفاصيل data-redirect=1، compare-bar سليم.

### 7.2 تحسين زرّي «التفاصيل» و«أضف للمقارنة» (دليل التخصصات)
- الزرّان النصيّان المخطوطان أسفليًا في بطاقات الدليل تحوّلا إلى **أزرار فعل** من النظام: `btn btn-primary` للتفاصيل (رابط يظل `<a href>`) و`btn btn-secondary` + أيقونة `plus` جديدة لـ«أضف للمقارنة» — بنفس النص حرفيًا.
- صفحة التفاصيل: زر «قارن هذا التخصص» صار `btn btn-primary` + أيقونة `plus`.
- **أمان العقد:** `compare-toggle` و`data-id/name/redirect` ما زالت على نفس عنصر الزر؛ النقر على الأيقونة داخل الزر يصلح لأن JS يستخدم `closest('.compare-toggle')`؛ الأيقونة `pointer-events-none`.
- أيقونتا `plus` و`x-mark` مضافتان لـ`x-ui.icon` (SVG مضمّن، لا مكتبة).
- التحقق: build ✅؛ test ✅ 282/1491؛ حيًا: compare-toggle ×10 + btn-secondary ×10 + plus ×10 + data-id/name ×10.

### 7.3 زر «إفراغ الاختيار»
- من رابط نصي مخطوط إلى `btn btn-secondary` + أيقونة `x-mark` جديدة، مع hover دلالي danger (حد/خلفية/نص) — إشارة «حذف» دون عدوانية.
- **العقد:** `#compare-bar-clear` يبقى على الزر نفسه (JS يفحص `event.target.id`)؛ الأيقونة `pointer-events-none` لتمرير النقرة دائمًا للزر. ✅ حيًا 1/1.

### 7.4 لمسة صفحة المقارنة — «لغة المبارزة»
- **هوية لونية لكل عمود:** بطاقتان بشريط علوي متدرّج (brand=الأول / accent=الثاني) وmonogram بنفس اللونين، ينعكسان في نقطتَي تلوين برأسي جدول المقارنة — تمييز بصري فوري بين العمودين يمتد من البطاقة إلى الجدول.
- **شارة «مقابل»** دائرية متدرّجة تتوسط البطاقتين (z-10 + ring عن خلفية الصفحة) — النسخ沿用 مستعار من JS نفسه (فاصل الأسماء « مقابل »)، والكلمة في `specializations.js:28` معروضة أصلًا للمستخدم. مخفية على الجوال (`hidden sm:flex`) حيث تتكدّس البطاقات.
- **الحرف الأول للـmonogram الثاني على accent:** النص `slate-900` وليس أبيض — التزامًا بقاعدة الوثيقة «النص على العنبري slate-900 دائمًا» (§5.1).
- **قرار زر «أضف للمقارنة»:** إبقاء النص ثابتًا (لا «إلغاء الإضافة»)، والتوصية بتعديل `specializations.js` (~4 أسطر aria-pressed + تبديل أيقونة) في مهمة منفصلة خارج #56. تجربة aria-pressed ثابتة من Blade **رُفضت وطُبِّقت بالخطأ ثم تراجعت**: حالة sessionStorage غير معلومة وقت render من Blade، لكانت تعرض «مُضاف» زيفًا.
- التحقق: build ✅ 14.64KB gzip؛ test ✅ 282/1491؛ حيًا: مقارنة (computer_science vs human_medicine) 200 — «مقابل» حاضرة، monogram ×2، نقطتا التلوين ×2، بلا أي قيمة حرة.

### 7.5 جولة صفحات المصادقة (دخول/تسجيل/استعادة/إعادة تعيين)
- **(أ) ترقية حالة الخطأ — CSS خالص:** قاعدة `.field-input[aria-invalid='true']` بحد `danger-600` + خلفية `danger-50/50` وحلقة تركيز حمراء — الحقل نفسه يحمرّ الآن وقت فشل التحقق (الـviews لم تتغير؛ `aria-invalid` كان يُكتب أصلًا).
- **(ب) هوية الجوال:** شارة «م مسارك» (نفس شعار الرأس حرفيًا، `aria-hidden`) أعلى `.auth-card` تظهر `lg:hidden` فقط — كسر فراغ الجوال حيث اللوحة الجانبية مخفية. النص «مسارك» هو اسم العلامة في `<title>` والرأس، لا صياغة جديدة.
- **(ج) عمق اللوحة الجانبية:** نمط نقاط `radial-gradient` خفيف فوق التدرّج في `.auth-panel` (CSS خام، لا Blade).
- **(د) الوصول:** زر إظهار كلمة المرور صار هدف 44px (`w-11` + دائري `rounded-s-lg` + hover خلفية) مع إبقاء البنية `svg[data-icon-show/hide]` كما هي تمامًا (عقد auth.js §2.2)؛ الحقل `ps-10→ps-12`. كل روابط ما تحت النماذج (نسيت كلمة المرور؟/إنشاء حساب/سجل الدخول/العودة) صارت `min-h-11`.
- **(هـ) رأس موحد للبطاقات:** ترويسة `auth-head` (أيقونة متدرّجة 44px + عنوان) — lock للدخول وإعادة تعيين، user-plus للتسجيل، envelope للاستعادة؛ ثلاث أيقونات جديدة في `x-ui.icon`. **زر الإرسال بقي نصًا خالصًا** (عقد `auth.js` يستبدل textContent).
- التحقق: build ✅ 14.91KB gzip؛ test ✅ 282/1491؛ حيًا: `data-password-toggle`/`data-icon-show/hide` سليمة (login 1/1/1، register ×2)، `@csrf`، زر «دخول» نصي بدون أيقونة، تدفق خطأ كامل (POST خاطئ ← 302 ← `aria-invalid` في الصفحة العائدة)، القواعد الثلاث الجديدة موجودة في المبنى.

### 7.6 أيقونات دلالية للتخصصات — `x-ui.spec-icon`
- **لماذا لم يُعدَّل `specializations.json`:** خارج ملفات UX-01 المسموحة، ويفحصه `SpecializationsDataTest`، والكاتالوج قرار محتوى (ملاطف) لا تجميل. الخريطة `match($id)` تعيش في المكوّن نفسه.
- **المكوّن:** شريحة متدرّجة بنفس لغة المبارزة/المعرض (brand/default، accent للعمود الثاني) + أيقونة SVG مضمّنة `aria-hidden` (زخرفية)، مقاسان md/lg، و**fallback تلقائي للحرف-الشعار لأي id غير معروف** — يحمي من إضافة تخصصات جديدة دون كسر العرض.
- **الأيقونات العشرة:** أكواد (حاسب)، قلب بنبض (طب)، درع بقلب (تمريض)، مسطرة T (مدنية)، أعمدة ومثلث (عمارة)، حقيبة (إدارة)، شبكة مفاتيح (محاسبة)، كفّتا ميزان (قانون)، شاشة بمؤشر (جرافيك)، زلّة مع خط الاستواء (ترجمة). أحادية اللون دائمًا — لا تلوين لكل تخصص (§5.4 وحجز RIASEC).
- **الاستهلاك:** بطاقات الدليل، ترويسة التفاصيل (lg)، بطاقتا المقارنة (brand/accent). العقد الحيّة سليمة: compare-toggle ×10، data-id ×10، العشرة chips كلها svg فعليًا (الحرف الوحيد في الصفحة هو «م» شعار الرأس).
- التحقق: build ✅ 14.92KB gzip؛ test ✅ 282/1491؛ render-test للـfallback والـtone في tinker ✅.

### 7.7 «عائلة الحقول» للتلوين الدلالي — رُفضت بمراجعة Alhareith وأُقرّ التوحيد
- **المسار:** نُفّذت باليت 10 ألوان دلالية لكل تخصص (تدرّجات + شرائط + نقاط + تباين محسوب ≥3.09:1) — ثم **رفضها المراجع الأول**: اللون قد يوحي بتفضيل تخصص على آخر، والهدف هوية موحّدة بلا ألوان-حاملة-معنى.
- **الحل المعتمد (الوضع الحالي):** كل الشرائح والنقاط موحّدة `brand`؛ التمييز البصري يتم **بشكل الأيقونة وحده** (نبضة/ميزان/مسطرة… عشر بصمات مختلفة بلا لون)؛ duotone glow وحركة **الرسم عند الـhover** باقيتان (إبداع شكلي لا لوني)؛ accent يظهر فقط كـ**وسم موضع** للعمود الثاني في «المبارزة» (شريط + dot رأس الجدول) — وظيفي مكاني لا قيمي؛ الـfallback للحرف-الشعار محفوظ لأي id جديد.
- **العبر: حتى بعد قرار Assignee بتوسيع النطاق، الكلمة الفصل للمراجع الأول في أي لون يحمل معنى.**
- التحقق: build ✅؛ test ✅ 282/1491؛ حيًا: لا بقايا `from-cyan|teal|emerald|amber|orange|blue|lime|rose|fuchsia|violet-600+` في أي صفحة تخصصات، `compare-toggle`/`data-id` ×10 سليمة، شرائح الأيقونات كلها `from-brand-500 to-brand-700`، شريط index موحّد brand→accent، والمقارنة brand للأول/accent للثاني.

### 7.8 Duotone فعلي + أزرار pill أنيقة (بعد ملاحظة Assignee: «الأيقونات بيضاء تمامًا»)
- **التشخيص:** الأيقونات كانت stroke أبيض موحدًا — تبدو نسخة واحدة؛ والأزرار `rounded-lg` بدت رسمية.
- **الحل (دون كسر قرار الحارث — لا ألوان دلالية):** كل أيقونة صارت **طبقتين بحبر أبيض واحد**: شكل ممتلئ `fill=currentColor opacity 0.28` يمنح كل تخصص شخصية (قلب ممتلئ للطب، درع للتمريض، سقف مثلث للعمارة، جسم حقيبة، جسد ميزان…) + خط تفصيل أبيض `1.6` فوقه (نبضة، صليب، أعمدة، خيط الحقيبة…) — التمييز الآن **بالشكل والكتلة** لا باللون. حركة الرسم hover تصيب طبقة الخط فقط (يبقىFill ثابتًا). أحجام الأيقونات رفعت (20→24px md) لتُقرأ الطبقتان.
- **الأزرار:** نمطان جديدان في `app.css` (`@layer components`، بلا !important): `.btn-soft` (تعبئة brand-50 ناعمة، hover 100) و`.btn-pill` (حواف كاملة). التوزيع: التفاصيل وقارن الآن وقارن هذا التخصص = primary pill؛ أضف للمقارنة = **soft pill**؛ إفراغ الاختيار = secondary pill + hover danger.
- **العقد:** `compare-toggle` مع `data-*` على الزر كما هو؛ `#compare-bar-link` و`#compare-bar-clear` على عنصريهما؛ الأيقونات `pointer-events-none`.
- التحقق: build ✅ 15.04KB gzip؛ test ✅ **282/1491**؛ حيًا: 10 fills duotone في index، toggle/data-id ×10، detail fill+pill، clear pill سليم.

### 7.9 تصحيح «الأيقونات بيضاء تمامًا» (ملاحظة Assignee المتكررة)
- **السبب الجذري:** الشريحة كانت تدرّج brand مشبعًا — الأبيض هو اللون الوحيد المقروء فوقها، فخرجت كل الأيقونات بيضاء مسطحة بغضّ النظر عن الطبقات.
- **العلاج:** قلب المعادلة — **شريحة فاتحة هادئة** (`from-brand-50 to-brand-200/70` مع `ring-brand-200`) و**حبر brand-700**: الخط التفصيلي بلون brand غامق، والطبقة الممتلئة بنفس الحبر بشفافية 0.22. هوية واحدة (لا لون دلالي — قرار الحارث محفوظ)، لكن الأيقونة صارت تُقرا بتباين حقيقي وشخصية. العمود الثاني في المبارزة: شريحة accent-50→100 بحبر accent-600 (وسم موضع لا هوية تخصص).
- **خلل ثانٍ صُحح:** حركة الرسم كانت تستهدف `.spec-draw path` والصنف نُقل خطأً إلى `<path>` — عاد الصنف إلى عنصر `<svg>`.
- التحقق: build ✅ 15.08KB؛ test ✅ **282/1491**؛ حيًا: 10 شرائح فاتحة، 10 حبر brand-700، **صفر** بياض متبقٍ، العقد toggle/data-id ×10 سليمة.

### 7.10 رفع البهت (بعد مراجعة بصرية من Assignee على لقطة)
- **السبب:** شريحة brand-50→200/70 شبه بيضاء على بطاقة بيضاء، حبر 700، stroke 1.7، fill 0.22 — تباين ضعيف عبر الطبقات الأربع.
- **العلاج (رفع تشبّع لا إرجاع أبيض):** شريحة `brand-100→brand-200` بحلقة `ring-brand-300/70` (تظهر كعنصر مستقل)؛ حبر `brand-800`؛ `stroke-width: 2`؛ طبقة fill `0.32`. العمود accent في المبارزة: حبر `slate-900` بدل accent-600 الباهت (نفس قاعدة §5.1: الداكن على العنبري).
- التحقق: build ✅؛ test ✅ **282/1491**؛ حيًا: 10× `text-brand-800`، 10× fill 0.32، 10× شريحة brand-200 بحلقة، stroke-2 في كل الأيقونات.

### 7.11 رفع حجم الأيقونات داخل الشريحة
- **الملاحظة (Assignee):** الأيقونة صغيرة. النسبة القديمة 24px/44px ≈ 54% (حد أدنى مقبول، ورسومات بمساحة داخلية تجعلها تبدو أصغر).
- **الحل:** md من `h-6` → `h-7` (~64% تعبئة)؛ lg من `h-8` → `h-9` — بدون تغيير مقاس الشريحة (44px هدف اللمس محفوظ، والبصمة البصرية للبطاقة ثابتة).
- التحقق: test ✅ 282/1491؛ حيًا: index ×10 h-7، التفاصيل h-9.

### 7.12 مراجعات Assignee النهائية لصفحة الإرشادات (intro)
- **توحيد دوائر الترقيم 1/2/3:** كانت الخطوة 1 `bg-brand-600` والخطوتان 2 و3 `bg-slate-800` — الآن كلها `bg-brand-600 text-white` (سطران في intro:135، 209؛ لا نصوص/عقد تغيّرت).
- **بقرار Assignee الصريح لا تغيَّر:** تسميات `uppercase tracking-wider` رغم مخالفتها §6، وصندوق الملاحظة (amber الخام)، وشرائح مستويات التقييم الخماسية (emerald/teal/slate/amber/rose) — الأخيرة أصلًا مرآة مقصودة لألوان أزرار التقييم التي يولّدها assessment.js، فبقيت.
- التحقق: build ✅؛ test ✅ **282/1491**؛ 3/3 دوائر موحّدة حيًا.

### 7.13 محاذاة الخطوة 3 مع 1 و2 (intro)
- **السبب الجذري (ملاحظة Assignee):** الخطوتان 1 و2 داخل بطاقات مصفّرة بينما عنوان الخطوة 3 كان خارج بطاقة (يطفو على حافة القسم) — انزياح يمين ~20px.
- **الحل:** تأطير الخطوة 3 بنفس هيكل الخطوة 2 حرفيًا: بطاقة `rounded-2xl bg-slate-50/50 p-5 sm:p-6` + صف `flex items-start gap-3.5` للدائرة والعنوان + المحتوى `ps-12` تحتهما. النصوص كما هي.
- التحقق: render مباشر — 3/3 صفوف متطابقة الهيكل، test ✅ 282/1491. (ملاحظة جانبية: تعذّر login الحيّ المتكرر سببه throttle:5,60 على نقطة الدخول — حماية تعمل كما صُممت، وليست خللًا.)

### 7.14 السلم الخماسي في intro — تخطيط تبايني متسق مع كل العروض
- **المشكلة:** `flex flex-wrap` لخمسة شرائح متفاوتة العرض = التفاف عشوائي غير متوازن على الجوال، وصف واحد طويل على المكتب.
- **الحل:** `grid grid-cols-1 sm:grid-cols-2` بتوزيع معبّر: +2 و+1 فوق، **المحايد بعرض كامل `sm:col-span-2`** وسطًا، -1 و-2 تحت — شكل «سُلَّم متباعد» يعكس بنية (+2→0→-2) بدل قائمة مسطحة. المحايد `justify-center`؛ الأربعة البقية `justify-center` لتوازن المحتوى داخل الخلايا العريضة.
- **RTL:** الترتيب المنطقي يقرأ يمينًا←يسارًا صحيحًا في كلتا الحالتين (+2 أقصى اليمين ثم +1… إلخ). الجوال: عمود واحد بالترتيب الكامل.
- النصوص والألوان (emerald/teal/slate/amber/rose) كما هي — لا تغيير (§7.12). التحقق: render ✅ البنية والأوامر، test ✅ 282/1491.

### 7.15 جولة تحسينات أخيرة (التخصصات + استكشاف ميولك) — بطلب «لا تستثنِ»
1. **صينية المقارنة:** شريط `#compare-bar` نُقل من أعلى الشبكة إلى **`fixed inset-x-4 bottom-4 z-40 mx-auto max-w-2xl`** بتعبئة بيضاء و`shadow-pop` و`ring-brand-200` — يبقى ظاهرًا أثناء تصفح 10 بطاقات. **العقد حرفيًا:** نفس العنصر بنفس الـ4 ids، `flex hidden` بنفس الترتيب، الأزرار pill كما هي. ✅ حيًا (flex hidden = 1، كل id = 1).
2. **حالة الفراغ:** `@if (empty($specializations) || count(...)===0) <x-ui.empty-state>` — أول استهلاك لمكوّن الحالات. **نص موقت («لا توجد تخصصات معروضة حاليًا / سنضيف التخصصات قريبًا. تابعنا.») يخضع لاعتماد ملاطف** قبل الدمج. ✅ الاختبار الاتجاهي: فارغة→تظهر، ممتلئة→تختفي.
3. **ترقيم البطاقات:** `text-slate-300 → text-slate-400` (مقروء ولا يسرق الانتباه).
4. **بانر الاستئناف (intro):** `role="alert" → role="status"` — ليس خطأً، معلومة؛ قارئ الشاشة لن يصرخ بعد الآن. (لا JS في intro، آمن.)
5. **القواعد العربية §6:** حُذف `tracking-tight` من **كل** العناوين العربية في views (home, layout, intro×3, show, specializations×3) — لا letter-spacing على العربية. الباقية `uppercase tracking-wider` استثناء صريح من Assignee (§7.12) ولم تُمس. ✅ grep: صفر tracking-tight.
6. **الصفحة الحالية في التنقل:** وسم `nav-link` على روابط الرأس الثلاثة + قاعدة `.nav-link[aria-current='page'] { bg-brand-50 text-brand-700 }` — التمييز البصري الآن يطابق `aria-current`. ✅ في المبنى وفي الرئيسية الحية.
- التحقق: build ✅؛ test ✅ **282/1491**؛ فحص حي شامل للتخصصات (ids ×4، toggle ×10، fixed ×1) والرئيسية (nav-link ×2+1) ✅.

### 7.16 إعادة بناء صفحة المقارنة (ملاحظتا Assignee + عيوب مكتشفة)
1. **حذف «الأول / الثانية»** من بطاقتي المقارنة (كانتا وسمين مزعجين فوق الاسم) — حذفٌ لنص أضفناه نحن في §7.4، لا لمس أي نص معتمد.
2. **شارة «مقابل» تعمل على كل العروض:** على المكتب تبقى متمركزة فوق الفاصل (absolute)، وعلى الجوال تنزلق بين البطاقتين داخل الشبكة (`order-2` في التدفق) مع **خطين فاصلين أفقيين** عن يمينها ويسارها — من «مفقودة تحت 768» إلى «فاصلة مقروءة». `pointer-events-none` على وضع المكتب المطلق (كان يسرق نقرات حواف البطاقتين).
3. **إعادة تصميم الجدول:** أعمدة مظللة بلون جهتها (`brand-50/30` للأول، `accent-50/40` للثاني) عبر رأس+خلايا متطابقة — **إلغاء zebra** لأنه كان يحارب تلوين العمودين؛ عمود «المحور» `th scope=row` بخلفية slate-50؛ markers ملوّنة لكل جهة؛ حوايا Card مقصوصة بـoverflow-hidden بدل div مزدوج؛ `—` الفراغات أخف (slate-300).
4. **نقاط دلالية/اتساق:** `scope="col"` على الرؤوس الثلاثة (صحح خطأً دلاليًا — كانت خلايا محور تُقرأ th بلا scope)؛ **إصلاح خلل§7.15:** العمود الثاني استعاد `tone="accent"` لشريحة الأيقونة (فُقد خطأً وبقي شريطه عنبريًا — تنافر brand/accent).
- التحقق: build ✅؛ test ✅ 282/1491؛ حيًا: الأول/الثاني = 0، «مقابل» = 1 بلا صنف hidden، scope-col ×3، scope-row ×4، تظليل ×8 خلايا، accent chip مستعاد.

### 7.17 تمرير الجدول على الجوال — عمود المحور لاصق بدل سهم
- **المشكلة (Assignee):** تحت ~640px الجدول (min-w-160) يحتاج سحبًا أفقيًا لا إيحاء به، ويختفي معه عمود «المحور».
- **لماذا لا سهم:** السهم الثابت يكذب (يبقى بعد آخر الجدول)، والصادق بلا JS يتطلب `@container scroll-state` — دعمها غير مضمون على أندرويد القديم المستهدف. **الحل الأصدق: العمود ثابت والمحتوى ينسحب تحته** — الإيحاء يأتي من الحركة نفسها، ولا يُفقد سياق الصف المقرأ.
- **التنفيذ:** `sticky start-0` لعمود المحور (z-20 رأس / z-10 صف + خلفية slate-50 معتمة)؛ تحويل الجدول `border-collapse → border-separate + border-spacing-0` (الحدود collapsed تختفي خلف الخلايا اللاصقة — خلل متصفح معروف) مع حدود per-cell، وحذف حد الصف الأخير بـ`[&>tr:last-child>*]:border-b-0`؛ خط فاصل 1px slate-200 على حافة العمود اللاصق (`.table-sticky-edge::after` في app.css). RTL-safe عبر `inset-inline-start`.
- التحقق: build ✅؛ test ✅ 282/1491؛ حيًا: sticky ×5 خلايا، border-separate، حدود الخلايا ×10.

### 7.18 انقلاب أيقونة التخصص عند مرور المؤشر على البطاقة (hover flip)
- **الطلب (Assignee):** عند المؤشر على بطاقة التخصص تصير الأيقونة زرقاء والرمز أبيض. **مفيد:** يضيف feedback تفاعليًا للبطاقة، واللون هنا **حالة تفاعل لا هوية** — لا يمس قرار Alhareith (§7.7: لا تلوين دلالي دائم لكل تخصص؛ جميعها brand واحد).
- **التقنية:** تدرّج CSS لا يُنتقَل بتغيير `from-*/to-*` (القيم ليست properties قابلة للتحويل)، فبدل تبديل التدرّج: طبقة `absolute inset-0` بتدرّج `from-brand-500 to-brand-700` بـ`opacity-0 group-hover:opacity-100` فوق الشريحة، والرمز `group-hover:text-white` عبر `transition-colors`. الشريحة صارت `relative overflow-hidden` (الحواف المقصوصة تحبس الطبقة). طبقة الـhover وtext-white تُحقن **لنبرة brand فقط** — accent (وسم موضع العمود الثاني) يبقى كما هو.
- **النطاق:** `spec-icon` يُقلّب داخل أي مجموعة `group`: بطاقات الدليل (card-lift group)، بطاقتا المقارنة (group موجود)، وترويسة show — أُضيف `group` لـ`<header>` عن طيب خاطر. الحرف-الشعار (fallback) ينقلب معه. `reduced-motion` يحوّل الانقلاب فوريًا (القاعدة العامة §7.2).
- **عقد:** `aria-hidden="true"` على الطبقة (زخرفة)؛ لا ids/data-*؛ `.compare-toggle` وأصنافه لم تُمس؛ لا تغيير نص.
- التحقق: build ✅ 15.49KB gzip؛ test ✅ 282/1491؛ حيًا (tinker render): brand chip فيه الطبقة + `group-hover:text-white`، accent نظيف (0 group-hover)، fallback ينقلب؛ CSS المبني يحوي `group-hover\:text-white`/`opacity-100`.

### 7.19 نافذة «استبدال التخصص الثالث» — قرار: تُترك كما هي (تُذكر في وصف PR)
- `window.confirm` في specializations.js:51 نافذة نظامية لا تقبل تنسيقًا بالـCSS؛ استبدالها بمودال بهويتنا يتطلب إعادة هيكلة الفرع المتزامن إلى غير متزامن في `resources/js/` — خارج نطاق UX-01 (views/css/public فقط). **بموافقة Assignee:** لا نلمسها، ويُذكر في وصف الـPR: «لم تُحوَّل نافذة تأكيد الاستبدال إلى مودال موحّد لأنها تستلزم تعديل JS خارج نطاق المهمة؛ مرشّحة لـissue تجربة استخدام منفصلة».

### 7.20 تقنين شكل الأزرار — قرار: لا تعديل بصري، القاعدة تُكتب
- **الملاحظة (Assignee):** هل هوية الأزرار موحّدة بين الصفحات أم أزرار التخصصات شاذة؟ **الجرد:** كل الأزرار (intro، show، auth×4، home، التخصصات) تمر عبر عائلة `.btn` — لون أولي `brand-600`، ثانوي رمادي، ارتفاع `min-h-11`، رفع+ظل عند hover، focus موحّد. لا اختلاف لوني/تنسيقي بين الصفحات. الفارق الوحيد: `btn-pill text-sm` في أزرار التخصصات (بطاقات الدليل، شريط المقارنة، زر التفاصيل).
- **القاعدة المقرّة:** الهوية واحدة مع variant له معنى سياقي —
  - `btn-pill text-sm`: أزرار داخل بطاقة/شريط أدوات مضغوط (مساحة أفقية محدودة بين عناصر) — كما في التخصصات.
  - `btn` الافتراضية (`rounded-lg text-base`): كل CTA في تدفق نموذج/صفحة (تقييم، مصادقة، الرئيسية).
- **لا يُعدّل أي زر بصريًا**؛ هذا السطر هو المرجع لمن قد يوحّد الأشكال عشوائيًا لاحقًا.

### 7.21 لمسات الهوية: الترويسة والتذييل
- **الترويسة:** (1) حدّ `border-slate-200/80` المُسطّح استُبدل بشريط هوية علوي بطول الصفحة `h-0.5` بتدرّج brand→accent (نفس شريط بطاقات الدليل §7.16 — لغة بصرية مشتركة، sticky مع الرأس). (2) مربّع «م» صار `from-brand-500 to-brand-700` بدل flat + `hover:text-brand-800` للاسم. (3) **الفراغ الوظيفي:** الزائر يرى الروابط الثلاثة ولا يملك أي CTA — أُضيف زر `btn btn-primary btn-pill text-sm` «تسجيل الدخول» لغير المسجّلين داخل نفس الـnav (يسري عليه §7.20: الرأس شريط مضغوط). كل الروابط والـids وnav-link/aria-current كما هي.
- **التذييل:** كان سطر «مسارك» يتيمًا — صار كتلة هوية داكنة `from-brand-800 to-brand-900` تعلوها مرآة شريط الترويسة (`h-1` brand→accent): الشعار «م» (placeholder كالرأس) + **التاجلاين المعتمد من README** (نص موجود، لا مستعار جديد) + روابط حالة (الرئيسية/التخصصات + دخول/حساب للزائر + ميولك للطالب + الإحصائيات للأدمن — كلها مسارات موجودة فقط، min-h-11، hover أبيض/10%). `mt-auto` على الـfooter (flex-1 على main يكفلها أصلًا).
- **تباين:** brand-100/90 على brand-800/900 ≈ 12:1+؛ أبيض على brand-600 (زر الدخول) ≈ 4.8:1. ✅
- التحقق: build ✅ 15.66KB؛ حيًا (tinker): شريط رأس×1، شريط تذييل×1، تدرّج التذييل×1، nav التذييل×1، زر دخول للزائر×1، التذييل القديم gone؛ test ✅ 282/1491.

### 7.22 زر «تسجيل الخروج» — خارج النطاق، مؤجل لـissue
- **الملاحظة:** لا يوجد أي مخرج من الجلسة في الواجهة رغم وجود مسار logout — ثغرة تجربة استخدام حقيقية. **القرار (Assignee):** ليست هوية بصرية، لا تُضاف في UX-01؛ تُذكر في وصف الـPR كـissue تجربة استخدام قادمة. الترويسة والتذييل يبقيان كما هما في §7.21.

### 7.23 مراجعة صفحات المصادقة الأربع — توحيد الرمز فقط (والباقي خارج النطاق)
- **الطلب (Assignee):** مراجعة login/register/forgot/reset. **القاعدة المقررة:** توحيد هوية بصرية فقط — لا حذف ولا إضافة ولا تعديل منطق/نصوص.
- **نُفِّذ:** رمز «م» في رؤوس البطاقات الأربع كان `bg-brand-600` مسطحًا بينما الرمز في الترويسة أصبح متدرّجًا (§7.21) — وُحّد إلى `bg-gradient-to-br from-brand-500 to-brand-700 + shadow-sm shadow-brand-600/30` مطابقًا حرفيًا للترويسة والتذييل. تعديل classes فقط.
- **رُفض/أُجّل (تسجيل، لا تنفيذ):** إخفاء CTA الرأس عن صفحة login (منطق)، تلوين حقل التأكيد الأحمر (توزيع رسائل backend)، readonly للبريد في reset (سلوك وظيفي)، رابط عودة بـreset (إضافة محتوى) — كلها خارج «توحيد الهوية» بقرار Assignee.
- **سليم بالفحص:** العقد (auth-form/نص زر الإرسال/data-target/data-icon) غير ممسوسة؛ التباين؛ auth-panel بتدرّجاته؛ field-toggle rtl-side.
- التحقق: build ✅؛ test ✅ 282/1491.

### تصحيح §7.18 (يُطبَّق فوقه): انقلاب accent أيضًا + إزالة CTA الرأس
- **نسيان §7.18 (Assignee):** شريحة العمود الثاني (accent) لم تكن تنقلب — حصرنا الطبقة في brand عمدًا («accent وسم موضع فقط»). **القرار: الاتساق يفوز** — الآن كلتا النبرتين تنقلب بنبرة جهتها: brand←brand-500→700، accent←accent-500→600 (أبيض على accent-600 ≈3.6:1، أيقونة زخرفية aria-hidden أصلًا). التنفيذ: `$hoverChip` يُمضي لون الطبقة بدل شرط `$isBrand`.
- **إزالة CTA «تسجيل الدخول» من الترويسة (§7.21):** قرار Assignee النهائي — «لا نضيف»: زر/رابط جديد ولو كان تنقلًا يخرج عن النطاق. الروابط الثلاثة كما كانت. روابط التذييل (§7.21) تبقى بإقرارها الصريح؛ تُنزع إن طُلب.
- التحقق: build ✅؛ tinker: طبقة accent + text-white present، brand unchanged؛ CSS المبني يحوي from-accent-500/to-accent-600؛ test ✅ 282/1491.
- **تصحيح §7.21 (Assignee):** قاعدة الجولة «توحيد فقط، لا إضافة» — زر «تسجيل الدخول» pill الذي أضفناه في رأس الزائر كان **إضافة محتوى/تنقّل جديدًا** لا توحيدًا، فنُزع. الرأس عاد لروابطه الثلاثة + خط الهوية + الشعار المتدرّج. روابط التذييل بقيت بإقرار Assignee الصريح في §7.21 (تنقل لصفحات موجودة)، وقابلة للنزع لو طُلب.
