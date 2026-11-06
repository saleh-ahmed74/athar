# تقرير سلامة قاعدة البيانات

## القاعدة والاختبارات

- database.default في تشغيل التطبيق المحلي هو mysql؛ لم أعرض أو أنسخ أي اعتماد من .env.
- PHPUnit/Pest يستخدم SQLite في الذاكرة. اجتازت المجموعة 83 test و430 assertion.
- migrate:status قُرئ بلا تعديل: 21 migration Ran وواحد Pending، وهو 2026_11_03_103727_add_storefront_performance_indexes.
- لم أنفذ migrate/rollback/fresh أو تغييراً على أي جدول أو بيانات.

## العلاقات والقيود في ملفات migrations

- الجداول تستخدم id() ومراجع foreignId؛ لم تتغير مفاتيح integer auto-increment.
- order_items.order_id → orders مع cascade.
- order_items.product_id وorders.user_id → السجلات المرتبطة مع nullOnDelete للحفاظ على سجل الطلب.
- products.category_id → categories مع restrictOnDelete.
- unique constraints معرفة للمستخدم email، slug/SKU، order_number وcheckout_token.
- checkout ينشئ الطلب والبنود ويخصم المخزون (إذا كان tracking مفعلاً) داخل transaction واحدة؛ تغييرات الحالة والإلغاء تستعمل معاملات وأقفالاً لاستعادة/خصم المخزون.
- البنود تخزن snapshot لاسم المنتج وسعره، ولا تعتمد على السعر الذي يرسله العميل.

هذه مراجعة تعريفات migrations وليست schema introspection كاملة لقاعدة البيانات الفعلية.

## ما لم يُختبر

لا يوجد fault injection لاختبار rollback، أو duplicate/concurrent checkout على MySQL، أو deadlock/قفل المخزون بين طلبين. SQLite memory لا تثبت locking أو DDL على MySQL. لم أختبر backup/restore؛ يلزم اختبار استعادة على قاعدة منفصلة.

## migration المعلقة

المigration تضيف فهارس للمنتجات والتصنيفات والطلبات والرسائل ولا تحذف البيانات حسب تعريفها. إنشاء index على جدول كبير قد يستهلك موارد أو يقفل الجدول. خذ نسخة احتياطية متحققة، راجع حجم الجداول ووقت التنفيذ على نسخة ممثلة، ولا تشغلها على الإنتاج دون موافقة صريحة.
