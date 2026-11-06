# جاهزية النشر على Hostinger Single Web Hosting

## توافق runtime

- Laravel 12.70.2 وشرط PHP في composer.json هو ^8.2، وهو يسمح نظرياً بـ PHP 8.4. CLI المحلي المتاح 8.2.29، لذلك تشغيل PHP 8.4 غير متحقق.
- توثيق Hostinger الحالي يذكر إصدارات 8.2 وأعلى حتى 8.5؛ اختر 8.4 من hPanel وتحقق من web runtime والامتدادات. https://www.hostinger.com/support/1575755-how-to-change-the-php-version-of-your-hosting-plan/
- مطلوب عملياً PDO MySQL وGD مع WebP وmbstring وfileinfo وOpenSSL وctype/tokenizer وXML/DOM؛ Intl مفيد. توفرت الأساسية محلياً، ولم أفحص إعداد الخادم.
- composer audit لا advisories. ابنِ vendor محلياً من lockfile من دون dev وارفع المجلد؛ لا تعتمد على Composer في Single.
- Vite production build نجح محلياً. ارفع public/build؛ Node ليس runtime لخدمة PHP بعد البناء.
- مثال الإنتاج يستخدم MySQL وfile cache/session وsync queue؛ لا Redis أو Docker أو worker. يوجد أمر framework افتراضي inspire مجدول hourly في routes/console.php، وليس مهمة أعمال؛ لا تشغّل scheduler ما لم تكن بحاجة إليه.

## قيد الخطة

دليل Hostinger يقول إن SSH متاح على Premium Web فما فوق وليس Single، وComposer CLI موصوف لخطط أعلى. هذا يجعل تشغيل أوامر artisan وإدارة vendor وstorage link غير محسوم على Single. https://www.hostinger.com/support/1583245-how-to-connect-to-a-hosting-plan-via-ssh-in-hostinger/ وhttps://www.hostinger.com/support/5792078-how-to-use-composer-at-hostinger/

قبل رفع المشروع، أكد في hPanel طريقة ضبط document root إلى public، نقل vendor كامل، إنشاء storage link أو بديله، صلاحيات storage وbootstrap/cache، وتشغيل أوامر Artisan الآمن. لا تنشئ endpoint ويب عام لتشغيل Artisan. إذا تعذر ذلك، خطة Single مانع عملي لاستضافة Laravel بهذه البنية.

## تسلسل النشر بعد حسم دعم الخطة

1. أصلح Git root: لا ترفع D:/Projects إلى GitHub، فهو بلا commits ويضم مجلدات أخرى untracked.
2. ابنِ محلياً أو CI: composer install --no-dev --prefer-dist --optimize-autoloader؛ npm ci؛ npm run build؛ ثم الاختبارات وaudits.
3. ارفع مصدر Laravel وvendor إلى مجلد خارج document root. اجعل document root يشير إلى public فقط؛ لا تضع .env أو .git أو storage أو vendor أو artisan أو composer files في web root.
4. أنشئ MySQL DB ومستخدماً محدوداً في hPanel. انسخ .env.production.example محلياً على الخادم كـ .env واملأ الأسرار هناك فقط. APP_DEBUG=false وAPP_KEY فريد وAPP_URL=https.
5. اختر PHP 8.4 وفعل extensions المطلوبة. اضبط صلاحيات ضيقة للكتابة على storage وbootstrap/cache، وتحقق من storage link ورفع الصور.
6. خذ نسخة قاعدة البيانات والصور واختبر استعادتها خارج الإنتاج. migration فهارس واحدة Pending؛ راجع أثرها ووقت تنفيذها. لا تنفذ أي migration إنتاجية ضمن هذا التدقيق ودون موافقة صريحة.
7. smoke test بعد النشر: home/shop/category/product/cart/checkout/login، SSL والcookies، صورة اختبار، ومنع وصول /.env و/.git و/vendor و/storage الخاصة و/artisan وملفات Composer.
8. وثق rollback للملفات وإجراء استرجاع قاعدة البيانات المتوافق مع schema.

## فروع GitHub بعد إصلاح المستودع

من جذر المستودع الصحيح وبعد مراجعة كل الملفات:
- git switch -c develop
- git push -u origin develop
- git switch -c production
- git push -u origin production

طوّر في feature branches تُدمج في develop بعد review/tests. ادمج develop إلى production بعد اعتماد الإصدار والنسخة الاحتياطية. لم أنشئ فروعاً أو أغير remote أو أدفع commits.
