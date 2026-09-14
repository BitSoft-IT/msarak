# مسارك

**منصة للتوجيه الأكاديمي والمهني لطلاب المرحلة الثانوية في اليمن.**

مسارك مشروع ويب يهدف إلى مساعدة الطالب على فهم الخيارات الأكاديمية واستكشاف ميوله قبل اتخاذ القرار الدراسي. التقييم في المشروع **استكشافي**؛ لا يمثل تشخيصًا نفسيًا أو اختبار قدرات، ولا يختار التخصص نيابة عن الطالب.

> **الحالة:** المشروع قيد تطوير النسخة الأولى (MVP). هذا الملف للتعريف والتشغيل فقط، بينما تبقى المتطلبات والقرارات التفصيلية في `docs/`.

## نطاق النسخة الأولى

وفق الوثائق المعتمدة، يستهدف المشروع:

- استعراض معلومات التخصصات ومقارنتها.
- إنشاء حساب وحفظ تقدم الطالب ونتائجه.
- تنفيذ تقييم استكشافي للميول.
- عرض نتيجة تفسيرية وتوصيات تساعد على البحث والمقارنة.

بنك الأسئلة المعتمد يتكون من **18 موقفًا × 4 خيارات**، ويستخدم **Holland RIASEC** كأساس للتصحيح.

> وجود وظيفة في نطاق الـMVP لا يعني أنها منفذة حاليًا؛ حالة التنفيذ الفعلية تُتابع من خلال Issues وPull Requests.

## التقنية المعتمدة

| الجزء | التقنية |
|---|---|
| Backend | Laravel 12.x |
| اللغة | PHP 8.2+ |
| الواجهة | Blade + Tailwind CSS |
| قاعدة البيانات | MySQL + Eloquent |
| بناء الواجهة | Vite |
| الاختبارات | PHPUnit عبر Laravel |
| إدارة المصدر | Git + GitHub |

المشروع تطبيق Laravel واحد، وليس Frontend وBackend منفصلين. تفاصيل القرارات التقنية موجودة في `docs/02-system-design/01-technical-decisions.md`.

## التشغيل محليًا

### المتطلبات

- PHP 8.2+
- Composer
- Node.js وnpm
- MySQL
- Git

### 1. استنساخ المشروع

```bash
git clone <repository-url>
cd msarak
composer install
npm ci
```

### 2. إعداد البيئة

Windows:

```powershell
copy .env.example .env
```

Linux / macOS:

```bash
cp .env.example .env
```

ثم:

```bash
php artisan key:generate
```

### 3. إعداد MySQL

أنشئ قاعدة بيانات محلية:

```sql
CREATE DATABASE masarak
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

ثم اضبط بيانات الاتصال المحلية في `.env` بما يناسب جهازك. الإعداد الأساسي للمشروع هو:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=masarak
```

لا ترفع `.env` أو كلمات المرور أو المفاتيح إلى GitHub.

### 4. التهيئة والتحقق

```bash
php artisan config:clear
php artisan migrate
php artisan test
npm run build
```

### 5. التشغيل

```bash
php artisan serve
```

وفي Terminal آخر عند تطوير الواجهة:

```bash
npm run dev
```

التطبيق المحلي:

```text
http://127.0.0.1:8000
```

فحص Laravel:

```text
http://127.0.0.1:8000/up
```

## هيكل المستودع

```text
msarak/
├── app/          # كود التطبيق
├── config/       # إعدادات Laravel
├── database/     # Migrations / Seeders / Factories
├── docs/         # وثائق المشروع المعتمدة
├── public/       # الملفات العامة ونقطة الدخول
├── resources/    # Blade / CSS / JavaScript
├── routes/       # مسارات التطبيق
├── storage/      # ملفات Laravel التشغيلية
├── tests/        # الاختبارات
├── .env.example
├── composer.json
├── package.json
└── README.md
```

## التوثيق

الـREADME بوابة للمشروع وليس بديلًا عن وثائقه.

| المجال | المرجع |
|---|---|
| التأسيس والمتطلبات | `docs/00-baseline/` |
| إدارة الفريق والعمل والمراجعة | `docs/01-management/` |
| القرارات التقنية والمعمارية والتصميم | `docs/02-system-design/` |
| Use Cases وWorkflows | `docs/03-system-analysis/` |
| الإطار العلمي وبنك الأسئلة | `docs/04-assessment/` |
| أوامر التنفيذ المهمة | `docs/commands/` |

عند وجود تعارض بين وثيقتين، لا يُحسم داخل README؛ يُراجع مصدر الحقيقة المختص ويُوثق القرار قبل تغيير السلوك أو النطاق.

## المساهمة

دورة العمل المعتمدة:

```text
Issue → Branch → Pull Request → Review → Squash Merge → Done
```

القواعد الأساسية:

1. كل تغيير يبدأ من Issue واضحة.
2. لا يتم العمل مباشرة على `main`.
3. ينشأ فرع مؤقت لكل Issue من أحدث `main`.
4. يلتزم المنفذ بنطاق المهمة ويشغّل الفحوص المناسبة.
5. يراجع التغيير عضو آخر مؤهل في المجال المتأثر.
6. بعد حل الملاحظات يتم `Squash Merge` ثم حذف الفرع المؤقت.

أسماء الفروع:

```text
feature/<issue>-<short-name>
fix/<issue>-<short-name>
test/<issue>-<short-name>
docs/<issue>-<short-name>
refactor/<issue>-<short-name>
```

قبل فتح Pull Request شغّل ما ينطبق على التغيير:

```bash
php artisan test
npm run build
```

تفاصيل إدارة العمل والمراجعة موجودة في:

- `docs/01-management/01-github-team-management.md`
- `docs/01-management/02-code-review.md`
- `docs/01-management/03-team-roles.md`

---

**مسارك يساعد الطالب على الاستكشاف واتخاذ قرار أكثر وعيًا؛ لا يتخذ القرار بدلًا عنه.**