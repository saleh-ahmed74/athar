# تقرير الأداء

## القياسات المتاحة

- بناء Vite الإنتاجي نجح بمجلد مؤقت خارج المشروع كي لا يستبدل الأصول الموجودة.
- الناتج CSS واحد app-H2hTMUU5.css، حجمه 374,032 بايت و46.89 kB gzip؛ manifest حجمه 131 بايت. لم يظهر ملف JS منفصل في build.
- صور product cards تستخدم lazy loading. رفع الصور يعيد ترميزها إلى WebP ويحدد الأبعاد.
- Pagination: shop 12، منتجات الإدارة 12، orders 15، الرسائل 15، سجل المستخدم 10.
- migration للفهرسة موجودة لكنها Pending على MySQL المحلية.

## مؤشرات غير مقاسة

| المؤشر | الحالة |
|---|---|
| Lighthouse score | NOT TESTED: غير مثبت ولا توجد URL حية |
| LCP / CLS / INP | NOT TESTED |
| query count / slow queries / EXPLAIN | NOT TESTED |
| p95 / p99 / RPS / HTTP errors | NOT TESTED؛ k6 غير مثبت |
| memory وبيانات أحجام الصور | NOT TESTED |

لا يمكن تقدير سعة المستخدمين من الاختبارات الوظيفية أو CSS build. لا أضع أرقام أداء لم تُقَس.

## ملاحظات الكود

- القوائم محدودة ومرقمة، والصفحة الرئيسية تحد نتائجها.
- إعداد StoreSetting يستخدم cache لمدة خمس دقائق وscoped instance.
- CSS minified يبلغ 46.89 kB gzip؛ القياس لا يشمل الصور وHTML والخطوط أو زمن الشبكة.
- الفهارس المقترحة قد تحسن الاستعلامات، لكن يجب تقييم EXPLAIN والحجم قبل تطبيق migration على بيانات كبيرة.
- ملفات الإنتاج تعتمد file cache/session وsync queue؛ لا Redis أو worker.

بعد تجهيز بيئة اختبار، شغّل Lighthouse على home/shop/product/cart/checkout. ابدأ k6 بـ smoke ثم 5/10/20/50 VUs وراجع الأرقام قبل مرحلة 100؛ script محلي فقط موجود في tests/load.
