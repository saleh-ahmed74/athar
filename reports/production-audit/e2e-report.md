# تقرير Browser E2E

## الحالة: NOT TESTED

Playwright غير موجود ضمن node_modules/dependencies. لم أضف package أو browsers أو test runner للمشروع، ولم أجر browser/mobile/network test أو ألتقط screenshots.

## الرحلات المطلوبة قبل النشر

1. Home → shop → category → product → add-to-cart.
2. cart guest persistence وتعديل/إزالة الكمية.
3. login/register/invalid credentials/logout/password reset من دون إرسال بريد فعلي.
4. checkout validation: بيانات ناقصة، هاتف غير صحيح، مدينة غير مسموحة وسلة فارغة.
5. تحقق server-side للأسعار ثم إنشاء طلب COD ببيانات وقاعدة اختبار فقط.
6. منع مستخدم من قراءة طلب مستخدم آخر، ورفض مستخدم غير admin من صفحات الإدارة.
7. رفع صورة سليمة ورفض ملف ليس صورة أو يتجاوز الحد.
8. responsive للهاتف: header/cart/checkout.
9. offline cache، حفظ draft، cart reconcile عند العودة، والتأكد أن order submit لا يعاد تلقائياً.

## التثبيت عند اعتماد dependency

من جذر المشروع: npm install -D @playwright/test ثم npx playwright install chromium، أضف config/specs ثم npx playwright test. راجع الدليل الرسمي https://playwright.dev/docs/intro . لم أشغل أوامر تثبيت لأنها تغير dependencies وlockfile.

لا توجد نسبة line coverage مرفقة؛ لا يتوفر Xdebug/PCOV في PHP المحلي.
