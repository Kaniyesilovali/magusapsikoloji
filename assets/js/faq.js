(function () {
  var pools = {
    tr: {
      depresyon: [
        { q: "Depresyon kendi kendine geçer mi?", a: "Hafif depresyon zaman zaman kendiliğinden hafifleyebilir; ancak orta ve ağır depresyon profesyonel destek almadan süreğen bir hal alabilir. Belirtiler iki haftadan uzun sürüyorsa bir psikolog veya psikiyatristile görüşmek önemlidir." },
        { q: "Depresyon ile üzüntü arasındaki fark nedir?", a: "Üzüntü belirli bir olaya bağlı geçici bir duygudur; depresyon ise nedensiz yoğun mutsuzluk, enerji kaybı, uyku bozuklukları ve günlük işlev kaybını kapsayan klinik bir tablodur. İki hafta veya daha uzun süren belirtiler depresyon işareti olabilir." },
        { q: "Depresyon için mutlaka ilaç almak zorunda mıyım?", a: "Hayır. Hafif-orta şiddette depresyonda psikoterapi — özellikle Bilişsel Davranışçı Terapi — tek başına etkili olabilir. Orta-ağır vakalarda psikiyatrist değerlendirmesiyle ilaç tedavisi terapiyi tamamlayabilir; ama karar hep bireysel değerlendirmeye göre verilir." },
        { q: "Depresyon tedavisi ne kadar sürer?", a: "Kişiden kişiye değişir. Hafif depresyonda 8-16 seans terapi çoğu zaman yeterlidir. Kronik veya ağır vakalarda süreç daha uzun olabilir. Terapist sizinle birlikte ilerlemeyi düzenli olarak değerlendirir." },
        { q: "Depresyon genetik midir?", a: "Genetik yatkınlık bir risk faktörüdür; ancak depresyon kaçınılmaz değildir. Stresli yaşam olayları, travmalar ve desteksiz çevre koşulları, genetiği olan kişilerde depresyonu tetikleyebilir. Terapi hem önleyici hem de iyileştirici rol oynar." },
        { q: "Depresyon günlük yaşamı ne ölçüde etkiler?", a: "Depresyon konsantrasyon güçlüğü, yorgunluk ve motivasyon kaybı yoluyla iş, okul ve sosyal ilişkileri ciddi biçimde etkileyebilir. Erken destek almak bu etkileri en aza indirmenin en etkili yoludur." },
        { q: "Sevdiğim biri depresyondaysa ona nasıl destek olabilirim?", a: "Yargılamadan dinlemek, 'seni görüyorum, seninleyim' mesajı vermek ve profesyonel yardım aramaya teşvik etmek en değerli destektir. Zorla iyileştirmeye ya da 'kendini topla' gibi tavsiyelerden kaçının." },
        { q: "Online terapi depresyon için işe yarar mı?", a: "Evet. Araştırmalar, video görüşme yoluyla yapılan BDT seanslarının yüz yüze terapi kadar etkili olabildiğini göstermektedir. Ulaşım sorunu yaşayanlar veya yoğun günlük programı olanlar için özellikle uygun bir seçenektir." }
      ],
      kaygi: [
        { q: "Kaygı bozukluğu neden oluşur?", a: "Genetik yatkınlık, uzun süreli stres, travmatik deneyimler ve öğrenilmiş düşünce kalıpları kaygı bozukluğunun başlıca nedenleri arasındadır. Beynin tehdit algılama sisteminin (amigdala) aşırı aktif olması da fizyolojik bir rol oynar." },
        { q: "Kaygı bozukluğu tamamen geçer mi?", a: "Kaygı bozukluğu, doğru tedaviyle büyük ölçüde kontrol altına alınabilir ve semptomlar belirgin şekilde azalabilir. Bilişsel Davranışçı Terapi ve gerektiğinde ilaç tedavisi en kanıta dayalı yaklaşımlar arasındadır." },
        { q: "Sürekli endişe duymak normal midir?", a: "Zaman zaman endişelenmek normaldir; ancak kontrol edilemeyen, her konuya yayılan ve altı aydan uzun süren endişe Yaygın Anksiyete Bozukluğu'na işaret edebilir. Günlük yaşamı sekteye uğratıyorsa profesyonel değerlendirme önerilir." },
        { q: "Kaygı bozukluğu fiziksel belirtiler verebilir mi?", a: "Evet. Çarpıntı, nefes darlığı, kas gerginliği, baş dönmesi, terleme ve mide sorunları kaygının sık görülen fiziksel belirtilerindendir. Bu nedenle önce tıbbi muayeneyle organik bir neden dışlanması önemlidir." },
        { q: "BDT kaygı tedavisinde gerçekten etkili midir?", a: "BDT (Bilişsel Davranışçı Terapi), kaygı bozukluklarında en güçlü bilimsel kanıta sahip terapi yöntemidir. Olumsuz düşünce kalıplarını fark etmeye, kaçınma davranışlarını azaltmaya ve uzun vadeli baş etme becerisi kazandırmaya odaklanır." },
        { q: "Kaygı için ilaç almadan iyileşmek mümkün mü?", a: "Evet, özellikle hafif-orta düzeyde kaygıda terapi tek başına çoğu zaman yeterlidir. Ağır vakalarda psikiyatrist değerlendirmesiyle ilaç tedavisi düşünülebilir; ancak uzun vadeli çözüm genellikle terapi ile sağlanır." },
        { q: "Kaygı anında ne yapabilirim?", a: "Diyafram solunumu (4 saniye nefes al, 4 tut, 6 ver), kas gevşeme egzersizleri ve zemin egzersizleri (5-4-3-2-1 duyusal odaklanma) anlık kaygıyı azaltmada etkilidir. Terapist, bu teknikleri sizin için özelleştirebilir." },
        { q: "Kaygı bozukluğu kalıcı bir durum mudur?", a: "Kaygı bozukluğu kronik bir süreç olabilir; ancak kronik olması sürekli acı çekileceği anlamına gelmez. Düzenli terapi ve gerektiğinde destekleyici ilaç tedavisiyle çoğu kişi normal yaşam kalitesine ulaşabilir." }
      ],
      "panik-atak": [
        { q: "Panik atak ne kadar sürer?", a: "Panik ataklar genellikle 10-20 dakika sürer ve semptomlar 30 dakika içinde kendiliğinden geriler. Atağın tehlikeli hissettirmesine karşın sona ermeden kalmaz; bu bilgiyi aklınızda tutmak atağı kısaltmaya yardımcı olabilir." },
        { q: "Panik atak öldürür mü?", a: "Hayır. Panik atak son derece rahatsız edici olsa da fiziksel olarak tehlikeli değildir. Kalp çarpıntısı, nefes darlığı ve uyuşma gibi belirtiler adrenalin artışından kaynaklanır; kalp krizi değil, bedenin tehdit moduna geçmesidir." },
        { q: "Panik atak ile kalp krizi nasıl ayırt edilir?", a: "Panik atakta belirtiler genellikle dakikalar içinde zirveye ulaşıp geriler; kalp krizinde ise göğüs ağrısı sol kola yayılabilir ve dinlenmekle geçmez. İlk atakta medikal değerlendirme yaptırmak önemlidir." },
        { q: "Panik bozukluk nedir?", a: "Tekrarlayan panik ataklar ve 'yeniden olacak mı?' kaygısıyla yaşam kalitesinin belirgin biçimde bozulması panik bozukluğu olarak tanımlanır. Terapi ile panik bozuklukta çok iyi sonuçlar alınmaktadır." },
        { q: "Panik atağı önlemenin yolları var mı?", a: "Düzenli nefes egzersizleri, tetikleyicileri tanıma ve BDT teknikleri panik ataklarının sıklığını ve şiddetini önemli ölçüde azaltabilir. Terapist, bireysel tetikleyicilerinizi belirlemenizde ve yönetmenizde yol gösterir." },
        { q: "Panik atak için ne zaman profesyonel yardım almalıyım?", a: "Ayda birden fazla panik atak yaşıyorsanız, ataklar arasında sürekli endişe taşıyorsanız veya atak korkusuyla belirli yerlere girmekten kaçınıyorsanız mutlaka bir psikologla görüşmeniz önerilir." },
        { q: "Online terapi panik atak için işe yarar mı?", a: "Evet. Video görüşmeli BDT seansları panik bozukluk için etkin biçimde uygulanabilmektedir. Atak geçirilen ortamlardan uzaklaşmak yerine kademeli maruziyet terapisi de online olarak yürütülebilir." },
        { q: "Panik atak sırasında ne yapmalıyım?", a: "Bulunduğunuz yerde kalın ve kaçmayın; kaçmak atağı güçlendirir. Yavaş, derin karın solunumu yapın. Kendinize 'bu geçecek, tehlikede değilim' mesajını tekrarlayın. Serinletici su veya soğuk yüz yıkama da yardımcı olabilir." }
      ],
      "terapiye-ne-zaman": [
        { q: "Terapiye gitmeye değer mi bilmiyorum, nasıl karar vermeliyim?", a: "Belirtiler günlük yaşamınızı, ilişkilerinizi veya iş performansınızı etkiliyor ve iki haftadan uzun süredir devam ediyorsa profesyonel destek almak uygun bir adımdır. Kesin bir kriz beklemek gerekmez; önleyici terapi de son derece değerlidir." },
        { q: "Terapide ne konuşulur?", a: "Terapi gündemleri kişiye ve sürece göre değişir; ancak genellikle duygu ve düşünce kalıpları, ilişkiler, geçmiş deneyimler ve gelecek hedefleri ele alınır. İlk seanslar değerlendirme ve güven inşasına ayrılır." },
        { q: "Terapi kaç seans sürer?", a: "Hedeflerinize ve zorlukların yoğunluğuna bağlı olarak değişir. Belirli bir probleme odaklanan kısa süreli BDT 8-16 seans olabilirken, uzun vadeli hedeflerde süreç daha uzun devam edebilir. İlk birkaç seansta terapistinizle tahmini süre konuşulur." },
        { q: "Her hafta terapiye gitmek zorunda mıyım?", a: "Klasik model haftada bir seansı öngörür; ancak pratik kısıtlamalar veya stabilizasyon döneminde iki haftada bir de sürdürülebilir. Terapistinizle ihtiyaçlarınıza göre bir program oluşturabilirsiniz." },
        { q: "Terapiye gittiğimi sevdiklerime nasıl söylerim?", a: "Söylemek zorunda değilsiniz; terapi gizlidir. Paylaşmak istiyorsanız bunu kendi hızınızda, güvendiğiniz kişilerle yapabilirsiniz. Terapi arayışı bir güç işareti olarak çevrenizle paylaşmak utanılacak bir şey değildir." },
        { q: "Terapi işe yaramazsa ne olur?", a: "Terapide ilerleme doğrusal olmayabilir ve terapist-danışan uyumu önemlidir. İlk terapistle bağ kuramadıysanız başka birini denemek çok normaldir. Birkaç seanstan sonra ilerleme hissetmiyorsanız bu durumu terapistinizle açıkça konuşun." },
        { q: "Sadece mutsuz olduğum için terapiye gidebilir miyim?", a: "Evet, kesinlikle. Terapi yalnızca kriz anlarında değil, yaşam kalitesini artırmak, kendinizi daha iyi tanımak veya belirli hedeflere ulaşmak için de başvurulabilir." },
        { q: "Online terapi yüz yüze terapi kadar etkili midir?", a: "Araştırmalar, çoğu psikolojik sorun için online terapinin yüz yüze terapi kadar etkili olduğunu göstermektedir. Özellikle ulaşım engeli veya zaman kısıtlaması olan bireyler için büyük bir kolaylık sağlar." }
      ],
      "terapiye-baslamadan": [
        { q: "İlk terapi seansında ne olur?", a: "İlk seans genellikle değerlendirme görüşmesidir; terapist neden geldiğinizi, geçmişinizi ve hedeflerinizi anlamaya çalışır. Çok şey söylemeniz beklenmez; tempo sizin rahat ettiğiniz düzeyde ilerler." },
        { q: "Terapiste her şeyi söylemek zorunda mıyım?", a: "Hayır. Terapi bir güven ilişkisidir ve anlatmak istediklerinizi kendi zamanlamanızda paylaşırsınız. Bir konuda konuşmaya hazır olmadığınızı belirtmeniz tamamen kabul edilebilir." },
        { q: "Doğru terapisti nasıl bulurum?", a: "Lisanslı bir psikolog veya psikolojik danışman olmasına dikkat edin. Uzmanlık alanının sorunlarınızla örtüşmesi ve ilk görüşmede güven hissetmeniz önemlidir. Birden fazla görüşme yaparak uyumu değerlendirebilirsiniz." },
        { q: "Terapi gizliliği nasıl işler?", a: "Terapistler yasal gizlilik yükümlülüğü altındadır; paylaşımlarınız üçüncü taraflarla paylaşılmaz. Yalnızca kendinize veya başkasına ciddi zarar riski olan durumlarda zorunlu bildirim söz konusu olabilir." },
        { q: "Hangi terapi türü bana uygun?", a: "BDT (Bilişsel Davranışçı Terapi) kaygı ve depresyonda geniş kanıt tabanına sahiptir. EMDR travma için özellikle etkilidir. Çift veya aile sorunlarında sistemik yaklaşımlar uygun olabilir. Terapistiniz ilk değerlendirmede size en uygun yaklaşımı önerir." },
        { q: "Terapiye devam etmek istemesem ne olur?", a: "Terapiyi bırakmak tamamen sizin kararınızdır. Bir seans sonrası devam etmek istemiyorsanız, mümkünse bunu terapistinizle konuşmanız hem size hem de terapiste değerli bir geri bildirim sağlar." },
        { q: "Terapi randevusu nasıl alınır?", a: "Web sitemizdeki iletişim formu veya WhatsApp hattı üzerinden bize ulaşabilirsiniz. İlk görüşme için uygun bir gün ve saat belirlemekten mutluluk duyarız." },
        { q: "Terapiye başlamadan önce ne hazırlamalıyım?", a: "Özel bir hazırlık gerekmez. Neden terapiye gelmek istediğinizi, şu an en çok sizi etkileyen şeyleri ve varsa hedefinizi aklınızda tutmanız yeterlidir. Kafanız karışıksa bile terapist sizi yönlendirmeye hazırdır." }
      ],
      "psikolog-psikiyatrist": [
        { q: "Psikolog ve psikiyatristin temel farkı nedir?", a: "Psikologlar psikoloji eğitimi almış terapi uzmanlarıdır; ilaç yazma yetkileri yoktur. Psikiyatristler ise tıp fakültesi mezunu, ilaç reçete edebilen ve biyolojik-psikolojik değerlendirme yapan hekimlerdir." },
        { q: "Psikiyatrist sadece ilaç mı yazar?", a: "Hayır. Birçok psikiyatrist psikoterapi de uygular; ancak uygulamak istedikleri çerçeve farklılık gösterebilir. Özellikle ilaç tedavisine ihtiyaç duyulduğunu düşünüyorsanız psikiyatristlik değerlendirmesi önemlidir." },
        { q: "Hangi durumda psikiyatrist görmeliyim?", a: "İlaç tedavisi düşünülen durumlarda (ağır depresyon, bipolar bozukluk, psikoz), semptomların biyolojik nedeni araştırılacaksa veya önceki psikolog görüşmesi ilaç önerisini beraberinde getirdiyse psikiyatristlik değerlendirmesi uygundur." },
        { q: "İlk görüşmemi kimle yapmalıyım?", a: "Çoğu durumda psikologla başlamak pratiktir; değerlendirme sonrası gerekirse psikiyatristlik yönlendirmesi yapılır. Belirgin biyolojik semptomlar (ağır uyku bozukluğu, kilo değişimi, intihar düşünceleri) varsa doğrudan psikiyatristlik önerilir." },
        { q: "Psikolog ve psikiyatrist birlikte çalışabilir mi?", a: "Evet, bu 'entegre tedavi' olarak bilinir ve en kapsamlı sonuçları verir. Psikiyatrist ilaç dozunu ve medikal takibi yönetirken psikolog terapi sürecini yürütür." },
        { q: "Kuzey Kıbrıs'ta psikolog bulmak zor mu?", a: "Gazimağusa'daki merkezimiz bölgede erişilebilir psikolojik destek sunmaktadır. Yüz yüze seansların yanı sıra online terapi da mevcut olduğundan ulaşım sorunu olan danışanlarımıza kolaylık sağlıyoruz." },
        { q: "Psikoloji ile psikiyatri hangi konularda örtüşür?", a: "Kaygı bozuklukları, depresyon, travma, uyum sorunları ve ilişki problemleri her iki uzmanlık alanında da ele alınabilir. Fark genellikle tedavi yönteminde ortaya çıkar: terapi mi, ilaç mı, yoksa ikisi birden mi?" },
        { q: "Psikolog seansları sigorta kapsar mı?", a: "Kuzey Kıbrıs'ta kapsam sigortaya göre değişir. Görüşme öncesinde sigorta şirketinizle iletişime geçmenizi öneririz. Bireysel ödeme planları hakkında da merkezimizle konuşabilirsiniz." }
      ],
      emdr: [
        { q: "EMDR hangi sorunlarda kullanılır?", a: "EMDR öncelikle travma ve TSSB (Travma Sonrası Stres Bozukluğu) için geliştirilmiştir. Bugün kaygı bozuklukları, fobiler, panik bozukluk, yas süreci ve öz-güven sorunlarında da etkin biçimde uygulanmaktadır." },
        { q: "EMDR kaç seans sürer?", a: "Travmanın türüne ve yoğunluğuna göre değişir. Tek olay travmasında 6-12 seans çoğu zaman yeterlidir; kronik veya çoklu travmalarda süreç daha uzun tutulabilir." },
        { q: "EMDR sırasında ne hissedebilirim?", a: "Seans sırasında travmatik anılara bağlı duygusal yoğunluk yaşanabilir; bu süreç terapistin rehberliğinde güvenli şekilde ilerler. Seansların ardından genellikle hafiflik ve çözülme hissi bildirilmektedir." },
        { q: "EMDR'nin bilimsel kanıtları var mı?", a: "Evet. EMDR, DSM ve Dünya Sağlık Örgütü başta olmak üzere pek çok uluslararası kuruluş tarafından travma tedavisinde etkin yöntem olarak onaylanmıştır. On yıllar boyunca yapılan araştırmalar etkinliğini doğrulamıştır." },
        { q: "EMDR ile BDT arasındaki fark nedir?", a: "BDT düşünce kalıplarını yeniden yapılandırmaya odaklanırken, EMDR travmatik anıların beynin bilgi işleme ağlarına entegrasyonunu hedefler. Her ikisi de kanıta dayalıdır; hangisinin daha uygun olduğunu terapist değerlendirir." },
        { q: "EMDR kötü anıları silip atar mı?", a: "Hayır. EMDR, anıları silmez; ancak bu anıların beyin tarafından işlenme biçimini dönüştürür. Amaç, anıyı 'aktif tehdit' olmaktan çıkarıp 'geçmişte kalmış bir olay' olarak deneyimleyebilmenizi sağlamaktır." },
        { q: "Online EMDR mümkün müdür?", a: "Evet, video görüşme yoluyla EMDR uygulanabilmektedir. Terapist alternatif yönlendirme teknikleri (ses veya tapping gibi) kullanarak sürecin etkinliğini korur." },
        { q: "EMDR kimler için uygun olmayabilir?", a: "Yoğun dissosiyasyon, aktif psikoz veya ileri düzey madde bağımlılığı gibi durumlarda terapist EMDR'ye başlamadan önce stabilizasyon çalışmasını önceliklendirir. Kişisel değerlendirme her zaman gereklidir." }
      ],
      "ogrenci-kaygisi": [
        { q: "Sınav kaygısı normal midir?", a: "Belirli düzeyde sınav kaygısı motivasyonu artırabilir ve normaldir. Ancak uyku sorunlarına, performans düşüklüğüne ve sosyal çekilmeye yol açan yoğun kaygı profesyonel destek gerektirir." },
        { q: "Sınav kaygısıyla başa çıkmanın pratik yolları nelerdir?", a: "Nefes tekniklerini uygulamak, çalışma planı yapmak, yeterli uyku almak ve sınav öncesi tekrar etmek yerine dinlenmek etkili stratejilerdir. BDT, sınav kaygısını derinlemesine ele almak için en kanıta dayalı yaklaşımdır." },
        { q: "Üniversite öğrencileri terapi için zaman bulabilir mi?", a: "Online terapi seçeneğiyle seansları yoğun üniversite takvimine uyarlamak mümkündür. Haftada bir, ders aralarına ya da akşam saatlerine randevu planlanabilir." },
        { q: "Yabancı uyruklu öğrenciler için Kuzey Kıbrıs'ta destek var mı?", a: "Evet. Merkezimiz İngilizce terapi hizmeti sunmaktadır. Uluslararası öğrenciler kültürel uyum güçlükleri, uzakta olmanın getirdiği yalnızlık ve akademik baskı konularında destek alabilir." },
        { q: "Terapiye gitmeye vaktim yok, ne yapabilirim?", a: "Online terapi bu sorunu büyük ölçüde çözmektedir: isteğe göre sabah, akşam veya hafta sonu seansları planlanabilir. 50 dakikalık bir seans ayda bile ciddi ilerleme sağlayabilir." },
        { q: "Kaygı ders çalışmamı engelliyorsa ne yapmalıyım?", a: "Bu 'kaçınma döngüsü' kaygının tipik bir belirtisidir. Bir psikologla görüşmek hem tetikleyicilerinizi anlamanıza hem de çalışma düzeninizi yeniden kurmanıza yardımcı olabilir." },
        { q: "DAÜ öğrencileri için özel psikolojik destek imkânları var mı?", a: "Gazimağusa'daki merkezimiz DAÜ kampüsüne yakın konumdadır ve öğrencilerin yoğun dönemlerine göre esnek randevu sunmaktadır. Online seanslar da mevcuttur." },
        { q: "Burs veya vize kaygısı gibi konularda terapi yardımcı olur mu?", a: "Evet. Belirsizlik ve kontrol kaybı hissi kaygının önemli kaynaklarıdır. Terapi bu duyguları yönetmeyi, öncelikleri netleştirmeyi ve karar alma sürecini kolaylaştırmayı sağlar." }
      ],
      "kuzey-kibris": [
        { q: "Kuzey Kıbrıs'ta psikolog bulmak zor mu?", a: "Seçenekler Türkiye veya Avrupa'ya kıyasla daha sınırlı olabilir; ancak Gazimağusa'daki merkezimiz bölgedeki boşluğu doldurmayı hedeflemektedir. Online terapi seçeneğiyle coğrafi engellemelerin büyük ölçüde aşılması mümkündür." },
        { q: "Kuzey Kıbrıs'ta hangi terapi hizmetleri sunuluyor?", a: "Bireysel terapi, çift terapisi, aile terapisi, çocuk psikolojisi ve EMDR hizmetleri sunulmaktadır. Hem yüz yüze hem de online seans seçenekleri mevcuttur." },
        { q: "İngilizce terapi alabilir miyim?", a: "Evet. Merkezimiz İngilizce danışmanlık hizmeti sunmaktadır; bu sayede uluslararası öğrenciler, expat'lar ve Kıbrıs Türkçesi dışında iletişim kurmayı tercih edenler de destekten yararlanabilir." },
        { q: "Kuzey Kıbrıs'ta terapi ücretleri nasıl?", a: "Ücretler terapistin deneyimine ve seans türüne göre değişmektedir. Merkezimizle iletişime geçerek güncel fiyatlar ve esnek ödeme seçenekleri hakkında bilgi alabilirsiniz." },
        { q: "Acil psikolojik destek için ne yapmalıyım?", a: "Acil bir kriz yaşıyorsanız lütfen en yakın sağlık kuruluşuna başvurun. Daha az acil fakat ivedi durumlarda merkezimizle WhatsApp üzerinden iletişime geçebilirsiniz; kısa sürede geri dönüş sağlarız." },
        { q: "Kuzey Kıbrıs'ta çocuk psikoloğu bulmak mümkün mü?", a: "Evet. Merkezimiz çocuk ve ergen psikolojisi hizmetleri de sunmaktadır. Oyun terapisi ve aile temelli yaklaşımlar çocuk danışmanlık süreçlerimizde kullanılmaktadır." },
        { q: "Online terapi Kıbrıs'ta yaygınlaşıyor mu?", a: "Evet, özellikle pandemi sonrası dönemde online terapi benimsenme hızla artmıştır. Adada ulaşım koşulları göz önünde bulundurulduğunda, online seans birçok kişi için pratik ve etkili bir çözüm sunmaktadır." },
        { q: "Mağusa Psikoloji Merkezi nerede bulunuyor?", a: "Merkezimiz Gazimağusa'da hizmet vermektedir. Adres bilgisi ve yönlendirme için web sitemizin iletişim sayfasını ya da WhatsApp hattımızı kullanabilirsiniz." }
      ],
      "cift-terapisi": [
        { q: "Çift terapisine ne zaman başlamalıyız?", a: "Sorunlar krizle sonuçlanmadan, iletişim güçlüğü, süregelen çatışma veya duygusal uzaklaşmanın ilk belirtilerinde başlamak en etkili zaman dilimidir. Ancak ileri aşamalarda da terapi değerli olabilir." },
        { q: "Çift terapisi evliliği kurtarır mı?", a: "Terapi garantili bir çözüm sunmaz; ancak çiftlerin birbirini daha iyi anlamasına, sağlıklı iletişim kurmasına ve ilişkinin geleceği konusunda bilinçli karar vermesine yardımcı olur. Bazı çiftler birlikte güçlenerek çıkar, bazıları ise sağlıklı bir ayrılık kararına ulaşabilir." },
        { q: "Partnerim terapiye gelmek istemiyorsa ne yapabilirim?", a: "Bireysel terapi, ilişki dinamiklerini anlamak ve değiştirmek için etkili bir başlangıç noktası olabilir. Bazen bir tarafın değişimi, diğer tarafın da terapiye dahil olmak istemesine zemin hazırlar." },
        { q: "Çift terapisinde neler konuşulur?", a: "İletişim kalıpları, güven sorunları, cinsellik, finansal anlaşmazlıklar, ebeveynlik farkları ve geçmiş travmaların ilişkiye yansımaları gibi konular ele alınabilir. Gündem çiftin ihtiyaçlarına göre şekillenir." },
        { q: "Çift terapisi kaç seans sürer?", a: "Genellikle 10-20 seans arasında değişir; ancak sorunların yoğunluğuna ve çiftin hedeflerine bağlı olarak süre uzayabilir. Terapist süreç boyunca ilerlemeyi birlikte değerlendirir." },
        { q: "Online çift terapisi etkili olabilir mi?", a: "Evet. Video görüşme yoluyla çift terapisi başarıyla uygulanmaktadır. Özellikle ulaşım güçlüğü olan veya yoğun programı bulunan çiftler için büyük bir esneklik sağlar." },
        { q: "Çift terapisi ayrılmaya neden olur mu?", a: "Terapi ayrılmayı teşvik etmez; ancak süreci açık bir şekilde ele alarak çiftin bilinçli kararlar vermesine yardımcı olur. Bazı çiftler netlik kazanarak daha sağlıklı bir karar alabilir." },
        { q: "Evli olmayan çiftler de çift terapisine gelebilir mi?", a: "Evet, kesinlikle. Çift terapisi evli olma koşulunu aramaz; ilişki dinamiklerini geliştirmek isteyen her çift için uygundur." }
      ],
      "aile-terapisi": [
        { q: "Aile terapisi ne zaman gereklidir?", a: "Süregelen aile içi çatışma, ergen çocuklarla iletişim güçlüğü, boşanma veya kayıp gibi büyük geçişler, çocukta davranış sorunları ve nesiller arası örüntüler aile terapisinin değerli olduğu durumlar arasındadır." },
        { q: "Aile terapisine herkes katılmak zorunda mı?", a: "İdeal olan tüm aile üyelerinin katılımıdır; ancak zorunlu değildir. Sadece birkaç üyeyle başlanan süreç bile aile dinamiklerinde olumlu değişimler yaratabilir." },
        { q: "Çocuk için aile terapisi mi bireysel terapi mi daha iyi?", a: "Çocuğun güçlükleri büyük ölçüde aile sisteminden kaynaklanıyorsa aile terapisi daha etkilidir. Çocuğun kişisel işleme ihtiyacı varsa her ikisi paralel yürütülebilir. Terapist değerlendirme sonrası öneride bulunur." },
        { q: "Ergen çocuk terapi sürecine nasıl dahil edilir?", a: "Ergenlerin sürece gönüllü katılımı için güvenli, yargısız bir ortam yaratmak önemlidir. Terapist önce ergenle bireysel bir bağ kurabilir, ardından aile seanslarına kademeli geçiş sağlanabilir." },
        { q: "Aile terapisi ne kadar sürer?", a: "Hedeflere bağlı olarak 8-20 seans arasında değişebilir. Kısa vadeli kriz müdahalelerinden uzun vadeli örüntü değişikliğine kadar geniş bir yelpazede uygulanabilir." },
        { q: "Online aile terapisi mümkün mü?", a: "Evet. Birden fazla kişinin aynı anda ekranda olduğu video görüşmesi aile terapisi için başarıyla uygulanmaktadır. Üyelerin farklı şehirlerde olduğu durumlarda özellikle değerlidir." },
        { q: "Boşanma sürecinde aile terapisi nasıl yardımcı olur?", a: "Boşanma sürecinde çocukların ihtiyaçlarını merkeze alan iletişim biçimlerini geliştirmek, ebeveynlerin ayrışırken çocuklara güvenli bir aile hissi yaşatmasına olanak tanır." },
        { q: "Aile terapisinde ne kadar özel bilgi paylaşılır?", a: "Terapist gizlilik ilkesine bağlıdır. Grup seanslarında herkesin duyduklarını saklı tutması beklenir; terapist bu konuyu başlangıçta açıkça ele alır." }
      ]
    },
    en: {
      depression: [
        { q: "Does depression go away on its own?", a: "Mild depression may lift over time; however, moderate to severe depression rarely resolves without professional support and can become chronic. If symptoms persist longer than two weeks, speaking with a psychologist or psychiatrist is recommended." },
        { q: "What is the difference between depression and sadness?", a: "Sadness is a temporary emotion linked to a specific event. Depression is a clinical condition characterised by persistent low mood, loss of energy, sleep disturbances, and impaired daily functioning—often with no clear trigger—lasting at least two weeks." },
        { q: "Do I have to take medication for depression?", a: "Not necessarily. For mild to moderate depression, psychotherapy—especially CBT—can be highly effective on its own. Medication is typically considered for moderate to severe cases and is always assessed on an individual basis." },
        { q: "How long does depression treatment take?", a: "It varies. Focused CBT for depression commonly runs 8–16 sessions. More complex or long-standing cases may take longer. Your therapist will regularly review progress with you and adjust the plan accordingly." },
        { q: "Is depression genetic?", a: "Genetic predisposition is a risk factor, but having family history does not mean depression is inevitable. Stressful life events, trauma, and lack of support are significant triggers, especially in those with a genetic vulnerability." },
        { q: "How does depression affect daily life?", a: "Depression can impair concentration, drain motivation, and disrupt work, study, and relationships. Seeking early support is the most effective way to minimise these impacts and prevent the condition from deepening." },
        { q: "Is online therapy effective for depression?", a: "Yes. Research shows that video-based CBT can be as effective as in-person therapy for depression. It is particularly suitable for those with transport difficulties or busy schedules." },
        { q: "How can I support someone with depression?", a: "Listen without judgement, let them know you are present, and gently encourage professional help. Avoid advice like 'just cheer up' or 'try harder'—these can unintentionally increase feelings of shame." }
      ],
      anxiety: [
        { q: "What causes anxiety disorder?", a: "Genetic predisposition, prolonged stress, traumatic experiences, and learned thought patterns are among the main contributors. An overactive threat-detection system in the brain (the amygdala) also plays a physiological role." },
        { q: "Can anxiety disorder be fully overcome?", a: "With the right treatment, anxiety can be significantly reduced and well-managed. Cognitive Behavioural Therapy (CBT) and, where appropriate, medication are the most evidence-based approaches." },
        { q: "Is constant worrying normal?", a: "Some worry is normal. However, persistent, uncontrollable worry that spreads to many areas of life and lasts more than six months may indicate Generalised Anxiety Disorder (GAD). If it disrupts daily functioning, professional assessment is recommended." },
        { q: "Can anxiety cause physical symptoms?", a: "Yes. Palpitations, shortness of breath, muscle tension, dizziness, sweating, and stomach discomfort are common physical manifestations of anxiety. A medical check-up to rule out organic causes is usually a helpful first step." },
        { q: "Is CBT genuinely effective for anxiety?", a: "CBT has the strongest evidence base among all therapies for anxiety disorders. It helps identify unhelpful thought patterns, reduce avoidance behaviours, and build lasting coping skills." },
        { q: "Can I recover from anxiety without medication?", a: "Yes, especially for mild to moderate anxiety. Therapy alone is often sufficient. For severe anxiety, a psychiatrist may recommend medication alongside therapy, but long-term improvement is generally sustained through the therapeutic work." },
        { q: "What can I do during an anxiety episode?", a: "Diaphragmatic breathing (inhale 4 sec, hold 4, exhale 6), progressive muscle relaxation, and grounding techniques (the 5-4-3-2-1 sensory method) are effective in the moment. A therapist can personalise these strategies for your triggers." },
        { q: "Is anxiety a permanent condition?", a: "Anxiety can become chronic, but that does not mean you will always be in distress. With consistent therapy and, where needed, medication, most people achieve a good quality of life." }
      ],
      "panic-attack": [
        { q: "How long does a panic attack last?", a: "Panic attacks typically peak within 10 minutes and resolve within 20–30 minutes. Knowing that every attack has an end can itself reduce its intensity." },
        { q: "Can a panic attack be dangerous?", a: "No. Although extremely distressing, panic attacks are not physically harmful. Palpitations, shortness of breath, and numbness result from a surge of adrenaline—your body's alarm system activating, not a medical emergency." },
        { q: "How do I tell the difference between a panic attack and a heart attack?", a: "In a panic attack, symptoms typically peak quickly and then subside. Heart attack chest pain often radiates to the left arm and jaw, and does not ease with rest. Medical evaluation is recommended during a first episode to rule out cardiac causes." },
        { q: "What is panic disorder?", a: "Panic disorder is diagnosed when repeated unexpected panic attacks are accompanied by persistent worry about future attacks or significant changes in behaviour to avoid them. It responds very well to treatment." },
        { q: "Are there ways to prevent panic attacks?", a: "Regular breathing exercises, identifying and addressing triggers, and CBT-based exposure work can substantially reduce the frequency and intensity of panic attacks." },
        { q: "When should I seek professional help for panic attacks?", a: "If you are having more than one attack per month, live in ongoing fear of the next attack, or are avoiding places or activities because of them, speaking with a psychologist is strongly advised." },
        { q: "What should I do during a panic attack?", a: "Stay where you are—fleeing reinforces the panic. Use slow belly breathing, and remind yourself: 'This will pass; I am not in danger.' Splashing cold water on your face can also help reset the nervous system." },
        { q: "Is online therapy effective for panic disorder?", a: "Yes. Video-based CBT is well-supported for panic disorder and can include guided exposure work. Many clients find the familiar home environment actually aids the process." }
      ],
      "when-therapy": [
        { q: "How do I know if I need therapy?", a: "If symptoms are affecting your daily functioning, relationships, or work performance, and have persisted for more than two weeks, professional support is a reasonable step. You do not need to be in crisis—therapy is also valuable for personal growth and prevention." },
        { q: "What happens in a therapy session?", a: "Sessions are tailored to you, but typically explore emotions, thought patterns, relationships, past experiences, and future goals. Early sessions focus on assessment and building trust; the pace is always led by your comfort." },
        { q: "How many sessions will I need?", a: "It depends on your goals and the complexity of the issues. Focused CBT may run 8–16 sessions; longer-term goals may require more. Your therapist will discuss a realistic timeframe after the first few sessions." },
        { q: "Do I have to attend every week?", a: "Weekly sessions are the standard model, but bi-weekly can also work well during more stable periods. You and your therapist will agree on a frequency that fits your needs and schedule." },
        { q: "Is online therapy as effective as face-to-face?", a: "Research consistently shows that online therapy is equally effective as in-person therapy for most psychological difficulties. It is especially convenient for those with transport constraints or demanding schedules." },
        { q: "Can I go to therapy just because I'm unhappy?", a: "Absolutely. Therapy is not only for crises. Feeling persistently flat, stuck, or disconnected are entirely valid reasons to seek support. Addressing concerns early often prevents them from escalating." },
        { q: "What if therapy doesn't work for me?", a: "Progress in therapy is rarely linear. If you feel stuck after several sessions, it is worth raising this openly with your therapist. Sometimes a change of approach or therapist is the right move—it is always your choice." },
        { q: "Do I have to tell people I'm in therapy?", a: "Not at all. Therapy is confidential. You may choose to share with trusted people in your own time, but there is absolutely no obligation to disclose." }
      ],
      "before-therapy": [
        { q: "What happens in the first therapy session?", a: "The first session is typically an assessment meeting. Your therapist will ask about your reasons for coming, your background, and your goals. You are not expected to share everything at once—the pace is yours." },
        { q: "Do I have to share everything with my therapist?", a: "No. Therapy is built on trust, and you share what you are ready to share. Saying 'I'm not ready to discuss that yet' is completely accepted." },
        { q: "How do I find the right therapist?", a: "Look for a licensed psychologist or counsellor whose area of expertise matches your concerns. Feeling safe and heard in the first session matters. It is perfectly normal to try more than one therapist before finding the right fit." },
        { q: "How does therapy confidentiality work?", a: "Therapists are bound by strict confidentiality obligations—your disclosures are not shared with third parties. The only exception is a serious risk of harm to yourself or others, in which case the therapist is legally required to act." },
        { q: "Which type of therapy is right for me?", a: "CBT has the broadest evidence base for anxiety and depression. EMDR is especially effective for trauma. Systemic approaches work well for relationship and family issues. Your therapist will recommend the best-fit approach after the initial assessment." },
        { q: "How do I book an appointment?", a: "You can reach us via the contact form on our website or through our WhatsApp line. We will be happy to find a day and time that works for you." },
        { q: "What should I prepare before my first session?", a: "No special preparation is needed. Simply have in mind why you are coming, what is affecting you most right now, and any goals you might have. Your therapist is ready to guide you even if everything feels unclear." },
        { q: "What if I want to stop after a few sessions?", a: "Continuing is entirely your decision. If you wish to discontinue, sharing that with your therapist—if possible—provides valuable feedback for both of you and helps ensure a positive closure." }
      ],
      "psychologist-psychiatrist": [
        { q: "What is the core difference between a psychologist and a psychiatrist?", a: "Psychologists are trained in psychology and provide therapy; they cannot prescribe medication. Psychiatrists are medical doctors who can prescribe medication and assess biological-psychological factors." },
        { q: "Do psychiatrists only prescribe medication?", a: "No. Many psychiatrists also provide psychotherapy. However, if you think medication may be needed—for severe depression, bipolar disorder, or psychosis—a psychiatric evaluation is the appropriate first step." },
        { q: "When should I see a psychiatrist?", a: "A psychiatrist is appropriate when medication is being considered, when symptoms appear to have a strong biological component (severe sleep disruption, significant weight changes, suicidal thoughts), or when a psychologist recommends a referral." },
        { q: "Who should I see first?", a: "In most cases, starting with a psychologist is practical. After assessment, a referral to a psychiatrist can be made if needed. For severe or acute symptoms, going directly to a psychiatrist is advisable." },
        { q: "Can a psychologist and psychiatrist work together?", a: "Yes—this is called integrated or collaborative care and often yields the best outcomes. The psychiatrist manages medication and medical monitoring while the psychologist leads the therapy process." },
        { q: "Is it easy to find a psychologist in North Cyprus?", a: "Options are more limited than in larger cities, but our centre in Famagusta aims to bridge that gap. Online therapy means geographic barriers are no longer a significant obstacle." },
        { q: "Where do psychology and psychiatry overlap?", a: "Anxiety, depression, trauma, adjustment difficulties, and relationship issues can be addressed by both specialists. The difference usually comes down to treatment modality: therapy, medication, or both." },
        { q: "Does insurance cover psychology sessions in North Cyprus?", a: "Coverage varies by insurer. We recommend contacting your insurance provider in advance. You are also welcome to discuss flexible payment options with our centre." }
      ],
      emdr: [
        { q: "What is EMDR used for?", a: "EMDR was originally developed for trauma and PTSD but is now widely used for anxiety disorders, phobias, panic disorder, grief, and self-esteem difficulties." },
        { q: "How many EMDR sessions are needed?", a: "It depends on the type and complexity of the trauma. Single-incident trauma often responds well within 6–12 sessions. Complex or multi-layered trauma typically requires a longer process." },
        { q: "What does EMDR feel like during a session?", a: "You may experience emotional intensity as traumatic memories are processed; this happens within the safe structure of the therapist's guidance. Most clients report a sense of release and lightness following sessions." },
        { q: "Is there scientific evidence for EMDR?", a: "Yes. EMDR is recognised as an evidence-based treatment for trauma by the World Health Organization and numerous international clinical bodies. Decades of research support its effectiveness." },
        { q: "How does EMDR differ from CBT?", a: "CBT works primarily by restructuring unhelpful thought patterns. EMDR focuses on integrating traumatic memories into the brain's information-processing networks. Both are evidence-based; your therapist can advise which suits you best." },
        { q: "Does EMDR erase bad memories?", a: "No. EMDR does not erase memories—it changes how the brain processes them. The goal is to move the memory from 'active threat' to 'past event', so it no longer triggers intense distress." },
        { q: "Is online EMDR possible?", a: "Yes. EMDR can be delivered effectively via video consultation using alternative bilateral stimulation techniques such as audio tones or self-administered tapping guided by the therapist." },
        { q: "Who might not be suitable for EMDR?", a: "Individuals with significant dissociation, active psychosis, or severe substance dependence may need stabilisation work before EMDR begins. A thorough personal assessment is always conducted first." }
      ],
      "student-wellbeing": [
        { q: "Is it normal to struggle with mental health as an international student?", a: "Yes. Adjusting to a new country, academic pressure, language barriers, and distance from family are all significant stressors. Experiencing anxiety, homesickness, or low mood in this context is very common." },
        { q: "Is therapy available in English in North Cyprus?", a: "Yes. Our centre offers counselling in English, making our services accessible to international students, expats, and anyone who prefers sessions in English." },
        { q: "Can I fit therapy into a university schedule?", a: "Online therapy makes this much easier. Sessions can be scheduled around lectures, including evenings and weekends. Even one session per week or fortnight can make a meaningful difference." },
        { q: "How can therapy help with academic pressure?", a: "Therapy helps identify the underlying thoughts driving perfectionism and procrastination, builds stress management skills, and breaks the avoidance cycles that worsen anxiety before deadlines or exams." },
        { q: "What if I feel homesick or isolated far from family?", a: "These feelings are valid and therapy provides a supportive space to process them. Building social connection on campus and maintaining family contact are also addressed as part of the process." },
        { q: "Is student therapy affordable?", a: "We offer flexible arrangements for students. Online sessions also remove travel costs. Please contact us to discuss a plan that fits your budget." },
        { q: "Can therapy help with visa or academic uncertainty?", a: "Yes. Uncertainty and perceived lack of control are key drivers of anxiety. Therapy helps you manage these feelings, clarify priorities, and make decisions with greater confidence." },
        { q: "What support exists specifically for DAU / EMU students in Famagusta?", a: "Our centre is conveniently located near the main Famagusta university campuses and offers flexible scheduling. Both in-person and online sessions are available." }
      ],
      "north-cyprus": [
        { q: "Is it hard to find a psychologist in North Cyprus?", a: "Compared to larger cities, options are more limited. Our centre in Famagusta is designed to address this gap. Online therapy further eliminates geographic barriers for people across the island." },
        { q: "What psychological services are available in Famagusta?", a: "We offer individual therapy, couples therapy, family therapy, child psychology, and EMDR. Sessions are available both in-person and online." },
        { q: "Can I receive therapy in English?", a: "Yes. Our centre provides counselling in English for international students, expats, and anyone who prefers to communicate in English." },
        { q: "How much does therapy cost in North Cyprus?", a: "Fees vary by therapist experience and session type. Please get in touch with us for current pricing and to discuss flexible payment options." },
        { q: "What should I do in a mental health crisis in North Cyprus?", a: "For an acute crisis, please go to the nearest hospital or emergency service. For urgent but non-emergency situations, you can contact us via WhatsApp and we will respond as quickly as possible." },
        { q: "Is child psychology available in North Cyprus?", a: "Yes. Our centre provides child and adolescent psychology services, including play therapy-informed approaches and family-centred support." },
        { q: "Is online therapy common in North Cyprus?", a: "It has grown considerably, especially since the pandemic. Given transport conditions on the island, online sessions are a practical and equally effective alternative for many clients." },
        { q: "Where is Magusa Psychology Centre located?", a: "We are based in Famagusta (Gazimağusa), North Cyprus. For our address and directions, please visit the contact page on our website or reach out via WhatsApp." }
      ],
      "exam-anxiety": [
        { q: "Is exam anxiety normal?", a: "A degree of exam anxiety is normal and can even sharpen focus. However, when anxiety causes sleep problems, blank-outs during exams, or makes you avoid studying altogether, professional support is worth seeking." },
        { q: "What are practical ways to cope with exam anxiety?", a: "Breathing exercises, structured study plans, adequate sleep, and active recall practice (rather than last-minute cramming) are effective strategies. CBT offers deeper, lasting tools for managing exam anxiety." },
        { q: "Does therapy help with performance anxiety specifically?", a: "Yes. CBT directly targets the perfectionist and catastrophic thinking patterns that drive performance anxiety. Exposure-based work and relaxation training can substantially improve exam performance." },
        { q: "Can I fit therapy into my university schedule?", a: "Online therapy is designed for busy schedules—sessions can be booked in the evenings, at weekends, or between lectures. Even a session every two weeks can produce significant change." },
        { q: "What if anxiety is stopping me from studying?", a: "Avoidance is a hallmark feature of anxiety. Talking to a psychologist can help you break the cycle, understand your triggers, and rebuild a sustainable study routine." },
        { q: "Is exam anxiety more common among international students?", a: "Yes. Academic pressure combines with cultural adjustment, language challenges, and distance from support networks—making international students particularly vulnerable. Support is available in English." },
        { q: "Are there breathing or grounding exercises I can try right now?", a: "Try box breathing: inhale for 4 seconds, hold for 4, exhale for 4, hold for 4. Repeat 4–5 times. Grounding—naming 5 things you see, 4 you can touch, 3 you hear—can also quickly reduce anxiety." },
        { q: "How does therapy differ from study skills coaching?", a: "Study skills coaching addresses techniques and habits; therapy addresses the emotional and cognitive patterns—perfectionism, fear of failure, self-criticism—that interfere with using those techniques effectively." }
      ],
      "couples-therapy": [
        { q: "When should we start couples therapy?", a: "The most effective time is early—when communication difficulties, recurring conflict, or emotional distance first appear. That said, therapy can be valuable at any stage of a relationship." },
        { q: "Can couples therapy save our relationship?", a: "Therapy provides tools to communicate better, understand each other more deeply, and make informed decisions about the future—but it does not guarantee a specific outcome. Some couples strengthen their bond; others reach a healthy closure." },
        { q: "What if my partner refuses to come to therapy?", a: "Individual therapy is an excellent starting point. Understanding your own role in the relationship dynamic and making personal changes often creates a shift that encourages the other partner to engage." },
        { q: "What topics does couples therapy cover?", a: "Communication patterns, trust, intimacy, financial disagreements, parenting differences, infidelity, and how past experiences affect the relationship are all common areas. The agenda is shaped by the couple's needs." },
        { q: "How many sessions does couples therapy take?", a: "Typically 10–20 sessions, though this varies. Progress is reviewed regularly and the plan adjusted according to how the couple is developing." },
        { q: "Can couples therapy work online?", a: "Yes. Video-based couples therapy is well-established and effective. It is especially useful when partners have demanding schedules or travel for work." },
        { q: "Does couples therapy lead to break-ups?", a: "Therapy does not steer couples toward separation. It creates a safe space for honest exploration—and sometimes that clarity does lead to a conscious decision to part. That, too, can be a healthy outcome." },
        { q: "Do we have to be married to attend couples therapy?", a: "No. Couples therapy is open to any couple—married, cohabiting, or dating—who want to improve their relationship or navigate a difficult period." }
      ],
      "family-therapy": [
        { q: "When is family therapy recommended?", a: "Persistent family conflict, communication breakdown with a teenager, major transitions (divorce, bereavement, relocation), and behavioural difficulties in a child are all situations where family therapy can be particularly helpful." },
        { q: "Does every family member have to attend?", a: "Ideally yes, but it is not mandatory. Progress can still be made with partial participation. Your therapist will work with whoever is willing to engage." },
        { q: "Is family therapy or individual therapy better for a child?", a: "If the child's difficulties are largely rooted in family dynamics, family therapy is usually more effective. Where a child also needs personal processing space, both can run in parallel." },
        { q: "How do you engage a reluctant teenager in therapy?", a: "Creating a safe, non-judgmental space is key. The therapist may begin with one-to-one sessions with the teenager to build trust before transitioning to joint family work." },
        { q: "How long does family therapy last?", a: "Typically 8–20 sessions, depending on goals. Short-term crisis support and longer-term pattern change are both achievable within the family therapy framework." },
        { q: "Is online family therapy possible?", a: "Yes. Video sessions with multiple family members are regularly used in family therapy. They are especially practical when family members live in different locations." },
        { q: "How does family therapy help during divorce?", a: "It helps parents maintain child-centred communication, reduce conflict, and ensure children experience a sense of security even as the family structure changes." },
        { q: "What about confidentiality in family sessions?", a: "The therapist is bound by professional confidentiality. Within group sessions, all participants are expected to respect what is shared; the therapist sets this expectation clearly from the outset." }
      ],
      "online-therapy": [
        { q: "Is online therapy as effective as in-person therapy?", a: "Research consistently shows that online therapy is equally effective as face-to-face for most psychological difficulties, including anxiety, depression, trauma, and relationship issues." },
        { q: "How does an online therapy session work?", a: "Sessions take place via a secure video platform. You join from a private, comfortable space of your choice. The experience closely mirrors in-person therapy in terms of depth and engagement." },
        { q: "Is online therapy private and confidential?", a: "Yes. We use encrypted video platforms, and therapist confidentiality obligations apply equally online. Ensure you are in a private space where you will not be overheard." },
        { q: "What technology do I need for online therapy?", a: "A stable internet connection, a smartphone, tablet, or computer with a camera and microphone, and a private space. No specialist software is required." },
        { q: "Can I have online therapy from anywhere in the world?", a: "Generally yes, though therapists are typically licensed to practice in specific jurisdictions. Please contact us to confirm we can work with you from your location." },
        { q: "Is online therapy suitable for children?", a: "Yes, with some adaptations. Younger children may need a parent present; older children and teenagers often engage very well online. The therapist will guide the format." },
        { q: "What if the internet connection drops during a session?", a: "Your therapist will attempt to reconnect immediately. If connection cannot be restored, the session will be rescheduled at no additional cost." },
        { q: "What are the advantages of online therapy in North Cyprus?", a: "Online therapy removes travel time across the island, allows scheduling flexibility, and makes it possible to access our services from any city in Cyprus or abroad." }
      ],
      "find-psychologist": [
        { q: "What should I look for when choosing a psychologist?", a: "Look for a licensed professional (MSc or doctorate in psychology, or equivalent). Consider their area of expertise, therapeutic approach, and whether you feel safe and understood after the first session." },
        { q: "How do I know if a therapist is qualified?", a: "Ask about their degree and licensure. In Cyprus, psychologists should be registered with the relevant professional body. A reputable centre will provide this information transparently." },
        { q: "What is the difference between a therapist, counsellor, and psychologist?", a: "'Therapist' is a broad term; 'psychologist' typically refers to someone with an advanced degree in psychology; 'counsellor' may have varying qualifications. In Cyprus, look for licensed psychologists or certified counsellors for structured therapy." },
        { q: "What if the first psychologist I see isn't the right fit?", a: "Therapeutic fit matters enormously. It is entirely normal—and advisable—to try another therapist if you do not feel comfortable or do not sense progress after 3–4 sessions." },
        { q: "Can I have an introductory call before committing to sessions?", a: "Yes. Many therapists, including ours, offer a brief introductory conversation so you can ask questions and assess fit before booking your first full session." },
        { q: "Is there an English-speaking psychologist in North Cyprus?", a: "Yes. Our centre in Famagusta offers counselling in English for international students, expats, and anyone who prefers English-language sessions." },
        { q: "How quickly can I get an appointment?", a: "We aim to offer an initial appointment within one week. For urgent situations, we will do our best to accommodate you sooner. Online sessions often allow faster scheduling." },
        { q: "What is a typical first session like?", a: "The first session is a conversational assessment. Your therapist will ask about your background, current concerns, and what you hope to achieve. You set the pace—nothing is rushed." }
      ]
    }
  };

  function shuffle(arr) {
    var a = arr.slice();
    for (var i = a.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var t = a[i]; a[i] = a[j]; a[j] = t;
    }
    return a;
  }

  function buildSchema(items, lang) {
    return {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": items.map(function(item) {
        return {
          "@type": "Question",
          "name": item.q,
          "acceptedAnswer": { "@type": "Answer", "text": item.a }
        };
      })
    };
  }

  function render(items, lang) {
    var title = lang === 'en' ? 'Frequently Asked Questions' : 'Sıkça Sorulan Sorular';
    var html = '<section class="py-12 bg-warm-secondary">'
      + '<div class="max-w-3xl mx-auto px-5 lg:px-8">'
      + '<h2 class="font-serif text-xl text-ink mb-6">' + title + '</h2>'
      + '<div class="space-y-3">';

    items.forEach(function(item, i) {
      html += '<div class="border border-warm-tertiary rounded-xl overflow-hidden bg-white">'
        + '<button type="button" aria-expanded="false" aria-controls="faq-answer-' + i + '"'
        + ' onclick="(function(btn){var ans=document.getElementById(\'faq-answer-' + i + '\');var open=ans.style.display===\'block\';ans.style.display=open?\'none\':\'block\';btn.setAttribute(\'aria-expanded\',!open);var icon=btn.querySelector(\'.faq-icon\');if(icon)icon.style.transform=open?\'rotate(0deg)\':\'rotate(45deg)\'})(this)"'
        + ' class="w-full text-left flex items-center justify-between gap-3 px-4 py-4 hover:bg-warm transition-colors">'
        + '<span class="text-sm font-medium text-ink leading-snug">' + item.q + '</span>'
        + '<svg class="faq-icon w-5 h-5 text-primary shrink-0 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
        + '</button>'
        + '<div id="faq-answer-' + i + '" style="display:none">'
        + '<p class="px-4 pb-4 text-sm text-ink-muted leading-relaxed">' + item.a + '</p>'
        + '</div>'
        + '</div>';
    });

    html += '</div></div></section>';
    return html;
  }

  function init() {
    var el = document.getElementById('blog-faq');
    if (!el) return;
    var topic = el.getAttribute('data-topic');
    var lang = el.getAttribute('data-lang') || 'tr';
    var langPool = pools[lang] || pools.tr;
    var pool = langPool[topic];
    if (!pool || !pool.length) return;

    var selected = shuffle(pool).slice(0, 4);

    var wrapper = document.createElement('div');
    wrapper.innerHTML = render(selected, lang);
    el.parentNode.insertBefore(wrapper.firstChild, el);
    el.parentNode.removeChild(el);

    var script = document.createElement('script');
    script.type = 'application/ld+json';
    script.text = JSON.stringify(buildSchema(selected, lang));
    document.head.appendChild(script);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
