# تقرير اختبار التحميل

## الحالة: NOT TESTED

k6 غير مثبت، ولم يجر اختبار حمل. لا توجد أرقام RPS أو المتوسط أو p95/p99 أو الأخطاء/timeouts. لم أختبر موقعاً خارجياً. لم أرسل POST أو اختبر checkout أو registration. لا أدعي سعة معينة.

تمت إضافة tests/load/storefront.js كسيناريو GET فقط ومقيد بـ localhost/127.0.0.1. يشمل الصفحة الرئيسية والمتجر، مع مسار product/category محلي اختياري. المراحل الافتراضية تصل إلى 50 VUs؛ مرحلة 100 تحتاج المتغير ALLOW_100_VUS=true بعد مراجعة النتائج السابقة يدوياً.

على Windows: ثبّت k6 عبر winget install k6 --source winget وفق الدليل الرسمي https://grafana.com/docs/k6/latest/set-up/install-k6/ ثم شغّل k6 run tests/load/storefront.js. شغل Laravel وقاعدة اختبار محلية أولاً، ولا تستخدم عنوان الإنتاج. احفظ نتائج التشغيل قبل تعديل الحمل. لم أضع thresholds اعتباطية قبل وجود baseline.
