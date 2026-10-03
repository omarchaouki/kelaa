<?php

/*
 * Legal and static pages in French, English and Arabic, written in the
 * first person plural ("nous" / "we"). Admins edit them afterwards in
 * Filament → Pages; the seeder never overwrites an existing page.
 */

return [
    'cgu' => [
        'fr' => <<<'HTML'
<h2>1. Objet</h2>
<p>Ces conditions encadrent l’utilisation de kelaa.ma, l’annuaire des services d’El Kelaa des Sraghna. En utilisant le site, vous les acceptez.</p>
<h2>2. Votre compte</h2>
<p>La consultation est libre et gratuite. Pour publier un avis, ajouter ou revendiquer un établissement, vous créez un compte avec votre numéro de mobile et un mot de passe. Vous êtes responsable de votre compte et de la confidentialité de votre mot de passe. Un seul compte par personne, avec un numéro qui vous appartient.</p>
<h2>3. Vos contenus</h2>
<p>Vos avis, réponses et photos doivent respecter nos <a href="regles-des-avis">règles des avis</a>. Vous nous accordez le droit gratuit de les afficher sur le site tant qu’ils sont publiés. Nous pouvons masquer ou supprimer tout contenu contraire à nos règles ou à la loi.</p>
<h2>4. Les établissements</h2>
<p>Les fiches sont données à titre d’information. Les professionnels peuvent revendiquer leur fiche après vérification. Le classement naturel ne s’achète pas : les offres payantes et les emplacements sponsorisés, toujours signalés « Sponsorisé », ne changent ni les notes ni l’ordre des résultats naturels.</p>
<h2>5. Offres payantes</h2>
<p>Les offres pour les professionnels sont activées après validation du paiement, et chaque paiement validé donne lieu à une facture.</p>
<h2>6. Responsabilité</h2>
<p>Nous faisons de notre mieux pour que les informations (horaires, pharmacies de garde…) soient exactes, sans pouvoir le garantir. En cas d’urgence, appelez le 15, le 19 ou le 150.</p>
<h2>7. Signaler un contenu</h2>
<p>Vous pouvez nous signaler tout contenu illicite avec le bouton « Signaler » ou en nous écrivant à contact@kelaa.ma.</p>
<h2>8. Droit applicable</h2>
<p>Ces conditions sont soumises au droit marocain.</p>
HTML,
        'en' => <<<'HTML'
<h2>1. Purpose</h2>
<p>These terms govern the use of kelaa.ma, the services directory of El Kelaa des Sraghna. By using the site, you accept them.</p>
<h2>2. Your account</h2>
<p>Browsing is free and open to all. To post a review, add or claim a business, you create an account with your mobile number and a password. You are responsible for your account and for keeping your password secret. One account per person, with a number that belongs to you.</p>
<h2>3. Your content</h2>
<p>Your reviews, replies and photos must follow our <a href="regles-des-avis">review guidelines</a>. You give us the free right to show them on the site while they are published. We may hide or remove any content that breaks our rules or the law.</p>
<h2>4. Businesses</h2>
<p>Listings are provided for information. Business owners can claim their listing after verification. Organic ranking cannot be bought: paid plans and sponsored placements, always labelled “Sponsored”, never change ratings or the order of organic results.</p>
<h2>5. Paid plans</h2>
<p>Business plans are activated once the payment is confirmed, and every confirmed payment comes with an invoice.</p>
<h2>6. Liability</h2>
<p>We do our best to keep information (opening hours, pharmacies on duty…) accurate, but we cannot guarantee it. In an emergency, call 15, 19 or 150.</p>
<h2>7. Reporting content</h2>
<p>You can report any unlawful content with the “Report” button or by writing to us at contact@kelaa.ma.</p>
<h2>8. Governing law</h2>
<p>These terms are governed by Moroccan law.</p>
HTML,
        'ar' => <<<'HTML'
<h2>1. الموضوع</h2>
<p>تنظم هذه الشروط استخدام kelaa.ma، دليل خدمات قلعة السراغنة. باستخدامك للموقع فإنك توافق عليها.</p>
<h2>2. حسابك</h2>
<p>التصفح حر ومجاني. لنشر تقييم أو إضافة مؤسسة أو المطالبة بها، تنشئ حساباً برقم هاتفك المحمول وكلمة مرور. أنت مسؤول عن حسابك وعن سرية كلمة المرور. حساب واحد لكل شخص، برقم يخصك.</p>
<h2>3. محتواك</h2>
<p>يجب أن تحترم تقييماتك وردودك وصورك <a href="regles-des-avis">قواعد التقييمات</a>. تمنحنا الحق المجاني في عرضها على الموقع ما دامت منشورة. يمكننا إخفاء أو حذف أي محتوى يخالف قواعدنا أو القانون.</p>
<h2>4. المؤسسات</h2>
<p>المعلومات في الصفحات للإرشاد فقط. يمكن للمهنيين المطالبة بصفحتهم بعد التحقق. الترتيب الطبيعي لا يُشترى: العروض المدفوعة والأماكن الممولة، المشار إليها دائماً بـ«ممول»، لا تغيّر التقييمات ولا ترتيب النتائج الطبيعية.</p>
<h2>5. العروض المدفوعة</h2>
<p>تُفعَّل عروض المهنيين بعد تأكيد الدفع، وكل دفعة مؤكدة تصاحبها فاتورة.</p>
<h2>6. المسؤولية</h2>
<p>نبذل قصارى جهدنا لتكون المعلومات (أوقات العمل، صيدليات الحراسة…) صحيحة، دون أن نضمن ذلك. في حالة الطوارئ اتصل بـ 15 أو 19 أو 150.</p>
<h2>7. الإبلاغ عن محتوى</h2>
<p>يمكنك الإبلاغ عن أي محتوى غير قانوني بزر «إبلاغ» أو بمراسلتنا على contact@kelaa.ma.</p>
<h2>8. القانون المطبق</h2>
<p>تخضع هذه الشروط للقانون المغربي.</p>
HTML,
    ],

    'confidentialite' => [
        'fr' => <<<'HTML'
<p>Nous protégeons vos données personnelles conformément à la loi marocaine n° 09-08.</p>
<h2>Les données que nous collectons</h2>
<ul>
<li>Compte : numéro de mobile, mot de passe (enregistré chiffré, nous ne pouvons pas le lire), nom affiché, langue.</li>
<li>Contributions : avis, notes, photos, réponses, signalements, suggestions de modification.</li>
<li>Professionnels : justificatifs de revendication, informations de facturation, reçus de paiement.</li>
<li>Statistiques : clics « appeler », « WhatsApp », « itinéraire », sans adresse IP (empreinte anonyme renouvelée chaque jour).</li>
<li>Position : seulement si vous l’autorisez, arrondie à environ 110 m et gardée dans votre session, jamais dans l’adresse de la page.</li>
</ul>
<h2>Pourquoi</h2>
<p>Pour faire fonctionner l’annuaire, publier et modérer les avis, lutter contre les abus, facturer les offres payantes et fournir des statistiques aux professionnels. Nous ne vendons jamais vos données.</p>
<h2>Combien de temps</h2>
<ul>
<li>Statistiques détaillées : 13 mois, puis seuls les totaux par jour sont gardés.</li>
<li>Compte et contributions : jusqu’à la suppression de votre compte.</li>
<li>Factures : 10 ans (obligation comptable).</li>
<li>Copies de vos données téléchargeables : 7 jours.</li>
</ul>
<h2>Qui y a accès</h2>
<p>Notre équipe de modération et nos prestataires techniques (hébergement, envoi d’e-mails), uniquement pour faire fonctionner le service.</p>
<h2>Vos droits</h2>
<p>Vous pouvez accéder à vos données, les corriger, vous opposer à leur traitement et les supprimer. Depuis la page <strong>Mes données</strong> de votre compte, vous téléchargez une copie de vos données et pouvez supprimer votre compte. À la suppression, vos avis restent signés « Utilisateur supprimé », sauf si vous nous demandez de les effacer. Pour toute autre demande : contact@kelaa.ma. Vous pouvez aussi saisir la CNDP (www.cndp.ma).</p>
HTML,
        'en' => <<<'HTML'
<p>We protect your personal data in line with Moroccan law no. 09-08.</p>
<h2>The data we collect</h2>
<ul>
<li>Account: mobile number, password (stored encrypted, we cannot read it), display name, language.</li>
<li>Contributions: reviews, ratings, photos, replies, reports, edit suggestions.</li>
<li>Businesses: claim documents, billing details, payment receipts.</li>
<li>Statistics: “call”, “WhatsApp” and “directions” clicks, without IP address (anonymous fingerprint renewed every day).</li>
<li>Location: only if you allow it, rounded to about 110 m and kept in your session, never in the page address.</li>
</ul>
<h2>Why</h2>
<p>To run the directory, publish and moderate reviews, prevent abuse, invoice paid plans and give statistics to business owners. We never sell your data.</p>
<h2>How long</h2>
<ul>
<li>Detailed statistics: 13 months, then only daily totals are kept.</li>
<li>Account and contributions: until you delete your account.</li>
<li>Invoices: 10 years (accounting obligation).</li>
<li>Downloadable copies of your data: 7 days.</li>
</ul>
<h2>Who can see it</h2>
<p>Our moderation team and our technical providers (hosting, e-mail delivery), only to run the service.</p>
<h2>Your rights</h2>
<p>You can access, correct, object to the processing of, and delete your data. From the <strong>My data</strong> page of your account, you can download a copy of your data and delete your account. When you delete it, your reviews remain signed “Deleted user” unless you ask us to remove them. For any other request: contact@kelaa.ma. You can also contact the CNDP (www.cndp.ma).</p>
HTML,
        'ar' => <<<'HTML'
<p>نحمي معطياتك الشخصية وفقاً للقانون المغربي رقم 09-08.</p>
<h2>المعطيات التي نجمعها</h2>
<ul>
<li>الحساب: رقم الهاتف المحمول، كلمة المرور (محفوظة مشفرة ولا يمكننا قراءتها)، الاسم الظاهر، اللغة.</li>
<li>المساهمات: التقييمات، النقط، الصور، الردود، البلاغات، اقتراحات التعديل.</li>
<li>المهنيون: وثائق المطالبة، معلومات الفوترة، وصولات الدفع.</li>
<li>الإحصائيات: نقرات «اتصال» و«واتساب» و«الاتجاهات» دون عنوان IP (بصمة مجهولة تتجدد كل يوم).</li>
<li>الموقع: فقط إذا سمحت بذلك، مقرّب إلى حوالي 110 م ومحفوظ في جلستك، وليس في عنوان الصفحة أبداً.</li>
</ul>
<h2>لماذا</h2>
<p>لتشغيل الدليل، ونشر التقييمات ومراقبتها، ومحاربة التجاوزات، وفوترة العروض المدفوعة، وتقديم إحصائيات للمهنيين. لا نبيع معطياتك أبداً.</p>
<h2>مدة الحفظ</h2>
<ul>
<li>الإحصائيات المفصلة: 13 شهراً، ثم نحتفظ فقط بالمجاميع اليومية.</li>
<li>الحساب والمساهمات: إلى حين حذف حسابك.</li>
<li>الفواتير: 10 سنوات (التزام محاسبي).</li>
<li>نسخ معطياتك القابلة للتحميل: 7 أيام.</li>
</ul>
<h2>من يطّلع عليها</h2>
<p>فريق المراقبة لدينا ومزودونا التقنيون (الاستضافة، إرسال البريد الإلكتروني)، فقط لتشغيل الخدمة.</p>
<h2>حقوقك</h2>
<p>يمكنك الاطلاع على معطياتك وتصحيحها والاعتراض على معالجتها وحذفها. من صفحة <strong>معطياتي</strong> في حسابك، يمكنك تحميل نسخة من معطياتك وحذف حسابك. عند الحذف تبقى تقييماتك موقعة باسم «مستخدم محذوف» إلا إذا طلبت منا حذفها. لأي طلب آخر: contact@kelaa.ma. يمكنك أيضاً اللجوء إلى اللجنة الوطنية لمراقبة حماية المعطيات (www.cndp.ma).</p>
HTML,
    ],

    'regles-des-avis' => [
        'fr' => <<<'HTML'
<h2>Un avis utile</h2>
<ul>
<li>Il raconte une expérience réelle et personnelle avec l’établissement, datant de moins de 12 mois.</li>
<li>Il est respectueux : pas d’insultes, de menaces, de propos discriminatoires ni d’informations personnelles (numéros, adresses, noms de salariés).</li>
<li>Il ne fait pas de publicité et ne contient pas de liens.</li>
<li>Il n’est pas écrit par le propriétaire, un salarié ou un concurrent de l’établissement, et il n’est pas payé.</li>
</ul>
<h2>Notre modération</h2>
<p>Nous vérifions certains avis avant leur publication (nouveaux comptes, mots signalés, nombre d’avis inhabituel). Nous pouvons masquer un avis qui ne respecte pas ces règles ; son auteur en est informé avec le motif.</p>
<h2>Ce que nous ne faisons jamais</h2>
<p>Un établissement ne peut pas nous payer pour supprimer un avis négatif ni pour améliorer sa note. Les professionnels peuvent répondre publiquement à chaque avis et nous signaler un avis contraire aux règles.</p>
HTML,
        'en' => <<<'HTML'
<h2>A useful review</h2>
<ul>
<li>It describes a real, personal experience with the business, from the last 12 months.</li>
<li>It is respectful: no insults, threats, discriminatory remarks or personal information (numbers, addresses, staff names).</li>
<li>It is not an advert and contains no links.</li>
<li>It is not written by the owner, an employee or a competitor of the business, and it is not paid for.</li>
</ul>
<h2>Our moderation</h2>
<p>We check some reviews before they are published (new accounts, flagged words, unusual number of reviews). We may hide a review that breaks these rules; its author is told why.</p>
<h2>What we never do</h2>
<p>A business cannot pay us to remove a negative review or to improve its rating. Business owners can reply publicly to every review and report a review that breaks the rules.</p>
HTML,
        'ar' => <<<'HTML'
<h2>تقييم مفيد</h2>
<ul>
<li>يحكي تجربة حقيقية وشخصية مع المؤسسة، لا يتجاوز عمرها 12 شهراً.</li>
<li>محترم: بدون سب أو تهديد أو كلام تمييزي أو معلومات شخصية (أرقام، عناوين، أسماء موظفين).</li>
<li>لا يتضمن إشهاراً ولا روابط.</li>
<li>لا يكتبه صاحب المؤسسة أو أحد موظفيها أو منافس لها، وليس مدفوع الأجر.</li>
</ul>
<h2>مراقبتنا</h2>
<p>نراجع بعض التقييمات قبل نشرها (حسابات جديدة، كلمات مبلغ عنها، عدد غير عادي من التقييمات). يمكننا إخفاء تقييم لا يحترم هذه القواعد، ويتم إخبار صاحبه بالسبب.</p>
<h2>ما لا نفعله أبداً</h2>
<p>لا يمكن لأي مؤسسة أن تدفع لنا لحذف تقييم سلبي أو لتحسين نقطتها. يمكن للمهنيين الرد علناً على كل تقييم والإبلاغ عن أي تقييم مخالف للقواعد.</p>
HTML,
    ],

    'cookies' => [
        'fr' => <<<'HTML'
<h2>Cookies nécessaires (toujours actifs)</h2>
<ul>
<li><strong>session</strong> : garde votre connexion pendant votre visite.</li>
<li><strong>XSRF-TOKEN</strong> : protège contre les envois de formulaires frauduleux.</li>
<li><strong>locale</strong> : mémorise votre langue.</li>
<li><strong>cookie_consent</strong> : mémorise votre choix sur les cookies (6 mois).</li>
</ul>
<h2>Cookies soumis à votre accord</h2>
<p>Aucun pour le moment. Si nous ajoutons une mesure d’audience, elle ne sera activée qu’avec votre accord. Vous pouvez changer d’avis à tout moment avec le lien « Gérer les cookies » en bas de page.</p>
<h2>Cartes</h2>
<p>Les fonds de carte viennent d’un fournisseur de tuiles cartographiques, qui reçoit votre adresse IP pour afficher les images.</p>
HTML,
        'en' => <<<'HTML'
<h2>Necessary cookies (always on)</h2>
<ul>
<li><strong>session</strong>: keeps you logged in during your visit.</li>
<li><strong>XSRF-TOKEN</strong>: protects against forged form submissions.</li>
<li><strong>locale</strong>: remembers your language.</li>
<li><strong>cookie_consent</strong>: remembers your cookie choice (6 months).</li>
</ul>
<h2>Cookies that need your consent</h2>
<p>None for now. If we add audience measurement, it will only run with your consent. You can change your mind at any time with the “Manage cookies” link at the bottom of the page.</p>
<h2>Maps</h2>
<p>Map backgrounds come from a map tile provider, which receives your IP address to display the images.</p>
HTML,
        'ar' => <<<'HTML'
<h2>ملفات ضرورية (مفعلة دائماً)</h2>
<ul>
<li><strong>session</strong>: تحافظ على تسجيل دخولك أثناء زيارتك.</li>
<li><strong>XSRF-TOKEN</strong>: تحمي من إرسال النماذج المزورة.</li>
<li><strong>locale</strong>: تتذكر لغتك.</li>
<li><strong>cookie_consent</strong>: تتذكر اختيارك بخصوص ملفات تعريف الارتباط (6 أشهر).</li>
</ul>
<h2>ملفات تحتاج موافقتك</h2>
<p>لا شيء حالياً. إذا أضفنا قياساً للزيارات فلن يُفعَّل إلا بموافقتك. يمكنك تغيير رأيك في أي وقت عبر رابط «إدارة ملفات تعريف الارتباط» أسفل الصفحة.</p>
<h2>الخرائط</h2>
<p>خلفيات الخرائط مصدرها مزود خرائط، يتلقى عنوان IP الخاص بك لعرض الصور.</p>
HTML,
    ],

    'mentions-legales' => [
        'fr' => <<<'HTML'
<h2>Qui sommes-nous</h2>
<p>kelaa.ma est édité par l’équipe kelaa.ma, à El Kelaa des Sraghna (Maroc).</p>
<h2>Nous contacter</h2>
<p>Par e-mail : contact@kelaa.ma.</p>
<h2>Hébergement</h2>
<p>Le site est hébergé par un prestataire professionnel. Ses coordonnées sont communiquées sur simple demande.</p>
<h2>Sources</h2>
<p>Une partie des fiches et les cartes proviennent d’OpenStreetMap (© contributeurs OpenStreetMap, licence ODbL : www.openstreetmap.org/copyright).</p>
<h2>Données personnelles</h2>
<p>Nous traitons vos données conformément à la loi n° 09-08. Voir notre <a href="confidentialite">politique de confidentialité</a>.</p>
HTML,
        'en' => <<<'HTML'
<h2>Who we are</h2>
<p>kelaa.ma is published by the kelaa.ma team, in El Kelaa des Sraghna (Morocco).</p>
<h2>Contact us</h2>
<p>By e-mail: contact@kelaa.ma.</p>
<h2>Hosting</h2>
<p>The site is hosted by a professional provider. Its details are available on request.</p>
<h2>Sources</h2>
<p>Some listings and the maps come from OpenStreetMap (© OpenStreetMap contributors, ODbL licence: www.openstreetmap.org/copyright).</p>
<h2>Personal data</h2>
<p>We process your data in line with law no. 09-08. See our <a href="confidentialite">privacy policy</a>.</p>
HTML,
        'ar' => <<<'HTML'
<h2>من نحن</h2>
<p>ينشر موقع kelaa.ma فريق kelaa.ma، بقلعة السراغنة (المغرب).</p>
<h2>اتصل بنا</h2>
<p>عبر البريد الإلكتروني: contact@kelaa.ma.</p>
<h2>الاستضافة</h2>
<p>الموقع مستضاف لدى مزود محترف. معلوماته متاحة عند الطلب.</p>
<h2>المصادر</h2>
<p>جزء من الصفحات والخرائط مصدره OpenStreetMap (© المساهمون في OpenStreetMap، رخصة ODbL: www.openstreetmap.org/copyright).</p>
<h2>المعطيات الشخصية</h2>
<p>نعالج معطياتك وفقاً للقانون رقم 09-08. اطلع على <a href="confidentialite">سياسة الخصوصية</a>.</p>
HTML,
    ],

    'a-propos' => [
        'fr' => <<<'HTML'
<p><strong>kelaa.ma</strong>, c’est l’annuaire des services d’El Kelaa des Sraghna : artisans, mécaniciens, pharmacies de garde, médecins, restaurants, commerces, agriculteurs et services publics, avec les avis des habitants.</p>
<p>Nous pensons que trouver le bon contact à la Kelaa ne devrait pas dépendre du bouche-à-oreille : chaque fiche donne le téléphone, le WhatsApp, les horaires et l’itinéraire.</p>
<p>Nous classons les résultats naturels selon la pertinence, la distance, les avis et l’activité de la fiche. Les emplacements sponsorisés sont toujours signalés et ne changent jamais les notes.</p>
HTML,
        'en' => <<<'HTML'
<p><strong>kelaa.ma</strong> is the services directory of El Kelaa des Sraghna: tradespeople, mechanics, pharmacies on duty, doctors, restaurants, shops, farmers and public services, with reviews from local residents.</p>
<p>We believe finding the right contact in Kelaa should not depend on word of mouth: every listing gives the phone, WhatsApp, opening hours and directions.</p>
<p>We rank organic results by relevance, distance, reviews and listing activity. Sponsored placements are always labelled and never change ratings.</p>
HTML,
        'ar' => <<<'HTML'
<p><strong>kelaa.ma</strong> هو دليل خدمات قلعة السراغنة: الحرفيون، الميكانيكيون، صيدليات الحراسة، الأطباء، المطاعم، المحلات، الفلاحون والمرافق العمومية، مع تقييمات السكان.</p>
<p>نؤمن بأن الوصول إلى الشخص المناسب في القلعة لا يجب أن يعتمد على الكلام المتداول: كل صفحة تعطي الهاتف والواتساب وأوقات العمل والطريق.</p>
<p>نرتب النتائج الطبيعية حسب الملاءمة والمسافة والتقييمات ونشاط الصفحة. الأماكن الممولة مشار إليها دائماً ولا تغيّر التقييمات أبداً.</p>
HTML,
    ],

    'contact' => [
        'fr' => <<<'HTML'
<p>Une question, une erreur sur une fiche, un contenu à signaler ? Écrivez-nous à contact@kelaa.ma, nous vous répondons rapidement.</p>
<p>Pour corriger une fiche, utilisez le lien « Suggérer une modification » sur la page de l’établissement. Les professionnels peuvent revendiquer leur fiche gratuitement.</p>
HTML,
        'en' => <<<'HTML'
<p>A question, a mistake on a listing, content to report? Write to us at contact@kelaa.ma and we will get back to you quickly.</p>
<p>To fix a listing, use the “Suggest an edit” link on the business page. Business owners can claim their listing for free.</p>
HTML,
        'ar' => <<<'HTML'
<p>سؤال، خطأ في صفحة، محتوى للإبلاغ عنه؟ راسلنا على contact@kelaa.ma وسنجيبك بسرعة.</p>
<p>لتصحيح صفحة، استعمل رابط «اقتراح تعديل» في صفحة المؤسسة. يمكن للمهنيين المطالبة بصفحتهم مجاناً.</p>
HTML,
    ],
];
