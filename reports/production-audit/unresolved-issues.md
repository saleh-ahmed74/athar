# المسائل غير المحسومة

1. Git root هو D:/Projects، بلا commits أولية، والملفات لا تظهر متتبعة؛ توجد ملفات untracked لمجلدات أخرى. لا ترفع هذا الجذر إلى GitHub. أصلح المستودع وراجع الملفات يدوياً. لم أغير global safe.directory أو remote أو branch.
2. Composer audit لم يجد advisories لكنه طبع تحذير dubious ownership عن D:/Projects. لم أعدل Git configuration؛ عالج root/ownership خارج هذا التدقيق بعد مراجعة صاحبه.
3. Hostinger Single بلا SSH حسب الوثائق؛ أكد document root ورفع vendor وتشغيل Artisan وstorage link. إن تعذر ذلك فالنشر محجوب حتى توفر وسيلة مدعومة أو خطة مختلفة.
4. PHP 8.4 لم يختبر؛ الجهاز يستخدم 8.2.29.
5. migration 2026_11_03_103727_add_storefront_performance_indexes معلقة؛ لا تنفذها على الإنتاج بلا backup وموافقة.
6. Lighthouse/LCP/CLS/INP وSQL query count وEXPLAIN غير مقاسة.
7. k6 غير مثبت؛ script local-only مضاف. تثبيت Windows: winget install k6 --source winget ثم k6 run tests/load/storefront.js. https://grafana.com/docs/k6/latest/set-up/install-k6/
8. Playwright غير مثبت؛ تثبيته بعد اعتماد dependency: npm install -D @playwright/test ثم npx playwright install chromium ثم npx playwright test. https://playwright.dev/docs/intro
9. Lighthouse غير مثبت؛ بعد تثبيت Chrome وNode: npm install -g lighthouse ثم lighthouse http://127.0.0.1:8000 . https://developer.chrome.com/docs/lighthouse/overview
10. ZAP غير مثبت؛ ثبت ZAP Desktop من https://www.zaproxy.org/download/ واستخدم passive-only على localhost. لم يتم active scan.
11. backup/restore، concurrency، rollback/fault injection على MySQL غير مختبرة.
12. CSP تسمح unsafe-inline وunsafe-eval؛ شددها فقط بعد إعداد nonce والتحقق من Livewire.
13. checkout draft يحتفظ ببيانات شخصية في localStorage لتلبية offline؛ قرر سياسة retention وأجهزة مشتركة، وامسحها بعد إتمام الطلب.
14. لم يتم تحقق HTTPS/proxy/cookies على Hostinger.

15. نسبة line coverage غير متاحة لأن Xdebug وPCOV غير مثبتين؛ لا توجد أرقام تغطية أسطر.
