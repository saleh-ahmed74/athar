# تقرير تدقيق الأمان

لا توجد ثغرة حرجة مؤكدة في الفحوصات المحلية. هذا ليس اختبار اختراق. Composer audit بلا advisories، وnpm audit صفر vulnerabilities. ZAP غير مثبت، ولم أختبر موقعاً خارجياً أو أشغّل active scan أو أرسل بريد/دفع حقيقي.

## نقاط الأمان الـ15

| # | المجال | الحالة | الدليل/الحد |
|---:|---|---|---|
| 1 | validation لمدخل checkout والبحث | PASS | تحقق خادمي، phone parser، وقائمة مناطق مسموحة |
| 2 | صلاحيات الإدارة | PASS | auth/admin middleware واختبارات |
| 3 | IDOR/ملكية الطلب | PASS | Order policy وسجل المستخدم وصفحة تأكيد محدودة |
| 4 | كلمات المرور وتسجيل الدخول | PASS جزئي | hashing وسياسة password واختبارات؛ لم يختبر متصفح حي |
| 5 | خصائص session cookies | NOT TESTED | مثال الإنتاج يضبط secure/http-only/SameSite؛ لم يفحص HTTPS حي |
| 6 | CSRF | PASS جزئي | web middleware وLivewire؛ لا اختبار browser |
| 7 | SQL injection | PASS جزئي | Eloquent/parameter binding؛ لا dynamic scan |
| 8 | XSS | PASS جزئي | Blade escaping هو النمط؛ لا فحص كامل للمتصفح |
| 9 | رفع الصور | PASS جزئي | validation، WebP re-encode، UUID، حد البكسلات واختبارات |
| 10 | rate limiting | PASS جزئي | تسجيل/reset/public/admin/checkout/contact؛ لا اختبار تجاوز |
| 11 | التلاعب بالسعر | PASS | الإجمالي يعاد حسابه من قاعدة البيانات على الخادم |
| 12 | ذرية الطلب ومنع التكرار | PASS جزئي | DB transaction وcheckout token؛ لا concurrency MySQL |
| 13 | headers وCSP | FAIL لتحسين hardening | headers متعددة موجودة، لكن CSP تسمح unsafe-inline وunsafe-eval |
| 14 | الأسرار وdebug | NOT TESTED جزئي | .env مستثنى وAPP_DEBUG=false في المثال؛ Git root بلا commits فلا يوجد فحص تاريخ موثوق |
| 15 | HTTPS/proxy trust | NOT TESTED | force HTTPS موجود بالإنتاج؛ TRUSTED_PROXIES تحتاج إعداداً موثوقاً وفحصاً حياً |

## ملاحظات وإجراءات

CSP الحالية أفضل من عدم وجودها لكنها تسمح inline/eval. لا تشددها قبل تجهيز nonce والتحقق من Livewire في كل الصفحات. بيانات checkout (الاسم والبريد والهاتف والعنوان والملاحظات) محفوظة في localStorage لتحقيق استكمال offline؛ لا تحفظ معلومات دفع. نظفها بعد الطلب، وراع الأجهزة المشتركة وسياسة الاحتفاظ. لا تضع .env أو أي أسرار في Git.

إعداد HTTPS/HSTS والكوكيز لم يختبر على Hostinger. اختبر response headers بعد التوجيه إلى HTTPS. إعداد TRUSTED_PROXIES يجب أن يحتوي فقط عناوين البروكسي الموثوقة عند وجودها، لا قيمة عامة.

لم يرسل التدقيق إشعارات فعلية. تدفق password reset الموجود يتطلب mail transport إذا كان مطلوباً تسليمه للمستخدم.

## ZAP

لم تتوفر أداة ZAP. للتثبيت استخدم ZAP Desktop الرسمي: https://www.zaproxy.org/download/ ، ثم passive-only على localhost. ZAP passive scanning لا يغير الطلبات حسب توثيقه: https://www.zaproxy.org/docs/desktop/addons/passive-scanner/ . لا تشغل active scan دون موافقة صريحة.
