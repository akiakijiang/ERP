<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
$act=$_REQUEST['act'];

if($act=='rejected_order' && $_SESSION['admin_name']=='ytchen'){
	global $db;
	if(isset ($_REQUEST['order_id']) && !empty($_REQUEST['order_id'])){
		$order_ids = array($_REQUEST['order_id']);
	}else{
		$order_ids = array(10460179,
10460192,
10460203,
10460598,
10460643,
10460646,
10460678,
10460681,
10460691,
10460809,
10460913,
10461008,
10461193,
10461228,
10461247,
10461293,
10461318,
10461369,
10461408,
10461417,
10461581,
10461584,
10461630,
10461650,
10461779,
10462319,
10462425,
10462868,
10462888,
10462922,
10462942,
10462949,
10462997,
10463189,
10463305,
10463356,
10463483,
10466273,
10466430,
10466855,
10466907,
10467881,
10468773,
10470779,
10471272,
10471298,
10471835,
10472028,
10472082,
10472219,
10472756,
10472982,
10475438,
10476068,
10476181,
10476631,
10476641,
10476714,
10476999,
10477080,
10477099,
10477263,
10477366,
10478842,
10479116,
10479691,
10479756,
10479905,
10480029,
10480217,
10480298,
10480446,
10480722,
10481253,
10483824,
10483899,
10484109,
10484333,
10484435,
10484694,
10484739,
10485088,
10485887,
10488929,
10489029,
10489087,
10489482,
10489521,
10489532,
10489744,
10490413,
10490518,
10491939,
10492185,
10492324,
10492335,
10492372,
10492895,
10493687,
10493907,
10495281,
10495300,
10495330,
10495365,
10495619,
10495813,
10495866,
10496038,
10496258,
10496730,
10496929,
10497043,
10497066,
10497378,
10498257,
10498450,
10498832,
10499574,
10499678,
10500397,
10500731,
10500789,
10500995,
10503177,
10503427,
10503799,
10503818,
10504220,
10504235,
10505778,
10506036,
10508204,
10509293,
10509630,
10510048,
10510425,
10510445,
10510790,
10510803,
10510847,
10512941,
10513335,
10513367,
10514091,
10514496,
10514844,
10518643,
10519251,
10520899,
10521371,
10521427,
10521980,
10522268,
10524802,
10525499,
10526702,
10527085,
10527231,
10527739,
10528086,
10530007,
10530298,
10530452,
10530705,
10531280,
10531636,
10532264,
10533163,
10535409,
10535918,
10536124,
10536163,
10540156,
10540789,
10541481,
10541529,
10541856,
10542917,
10543334,
10545562,
10545907,
10546053,
10546546,
10547317,
10547642,
10548170,
10548271,
10550072,
10550488,
10550809,
10551129,
10551423,
10552206,
10552213,
10552302,
10553565,
10553873,
10554620,
10554649,
10555407,
10555426,
10560678,
10561218,
10561637,
10561673,
10561785,
10561816,
10562728,
10562776,
10562851,
10564446,
10564972,
10565072,
10565099,
10565690,
10566300,
10566427,
10566608,
10567184,
10567596,
10570958,
10571031,
10571717,
10571827,
10572202,
10572456,
10572617,
10572996,
10573055,
10574453,
10574474,
10574715,
10574961,
10575377,
10575426,
10575541,
10575571,
10575617,
10575905,
10578508,
10578571,
10578952,
10579938,
10579980,
10579984,
10580230,
10580245,
10580280,
10580632,
10580891,
10581185,
10581952,
10582015,
10582506,
10582548,
10582564,
10583642,
10583838,
10585924,
10586449,
10586711,
10587406,
10587539,
10587887,
10588561,
10588588,
10588771,
10588835,
10588974,
10590857,
10591194,
10591668,
10591704,
10592269,
10592745,
10593088,
10593972,
10594064,
10594450,
10596185,
10596389,
10596857,
10597178,
10597580,
10598030,
10598672,
10599205,
10603675,
10604061,
10604446,
10605381,
10605577,
10606132,
10607081,
10607133,
10607188,
10607771,
10607842,
10607892,
10608693,
10609405,
10609581,
10609808,
10609927,
10610324,
10610453,
10610496,
10610559,
10612720,
10612779,
10613218,
10613337,
10614325,
10614612,
10614682,
10614756,
10616849,
10617967,
10617981,
10618034,
10618346,
10619748,
10619770,
10620007,
10621940,
10622265,
10622536,
10623403,
10623809,
10623869,
10624917,
10625119,
10625216,
10625425,
10625807,
10626035,
10626260,
10626700,
10626843,
10626996,
10627119,
10627195,
10628163,
10628314,
10629685,
10630386,
10630484,
10631245,
10631706,
10631917,
10632887,
10633219,
10637211,
10637314,
10637402,
10637523,
10637837,
10638575,
10640408,
10642310,
10642407,
10642717,
10642723,
10642835,
10642865,
10642912,
10643611,
10643822,
10644379,
10644528,
10644542,
10645352,
10645462,
10647141,
10647158,
10647477,
10647812,
10648058,
10648084,
10648891,
10648935,
10651204,
10651286,
10651650,
10651700,
10651935,
10653232,
10653489,
10654384,
10654515,
10656157,
10656711,
10657021,
10658498,
10659330,
10659465,
10661824,
10662306,
10662704,
10662955,
10662968,
10663000,
10666871,
10666930,
10667157,
10667360,
10667684,
10667902,
10668018,
10668713,
10669017,
10671260,
10671377,
10671748,
10671996,
10672142,
10672503,
10672674,
10672780,
10673003,
10673353,
10673408,
10673849,
10675606,
10677477,
10677906,
10679730,
10679911,
10679965,
10680276,
10680313,
10680504,
10680981,
10681133,
10682044,
10682197,
10682202,
10682288,
10682559,
10682671,
10682858,
10684810,
10684992,
10685138,
10685563,
10686054,
10686647,
10686710,
10687221,
10687269,
10690301,
10690357,
10690768,
10690778,
10691082,
10692768,
10694025,
10694273,
10694403,
10694889,
10695569,
10695766,
10696133,
10696289,
10696669,
10697393,
10697709,
10698222,
10698863,
10700083,
10700163,
10700362,
10702117,
10702481,
10702589,
10702770,
10703210,
10703742,
10705261,
10706389,
10706565,
10707115,
10707279,
10707606,
10707672,
10707935,
10708026,
10708373,
10709245,
10709469,
10710146,
10710198,
10710674,
10712440,
10713318,
10713443,
10713621,
10714436,
10714533,
10714538,
10719100,
10719260,
10719503,
10719518,
10719638,
10719721,
10719751,
10720235,
10720287,
10720428,
10720879,
10721689,
10721811,
10722499,
10722814,
10722890,
10722954,
10723092,
10723129,
10723259,
10723323,
10723350,
10723355,
10723463,
10723527,
10723536,
10723562,
10723593,
10723629,
10723650,
10723767,
10723846,
10723852,
10723896,
10723960,
10724068,
10724073,
10724080,
10724239,
10724273,
10724352,
10724417,
10724440,
10724529,
10724622,
10724699,
10724726,
10724904,
10724911,
10724934,
10724978,
10725031,
10725082,
10725147,
10727580,
10727611,
10727643,
10727963,
10727972,
10728176,
10728258,
10728287,
10728606,
10728670,
10728675,
10728713,
10728764,
10728893,
10729070,
10729206,
10729320,
10729408,
10729471,
10729486,
10729574,
10729621,
10729713,
10729718,
10729755,
10729797,
10729860,
10730029,
10730056,
10730340,
10730462,
10730751,
10730804,
10730921,
10731026,
10731081,
10731154,
10741513,
10741616,
10741994,
10742143,
10742152,
10742329,
10742335,
10742402,
10742434,
10742604,
10742713,
10742923,
10743046,
10743184,
10743189,
10743240,
10743300,
10743365,
10743468,
10743535,
10743673,
10743724,
10743733,
10743906,
10743932,
10743964,
10744012,
10744123,
10744213,
10744277,
10744289,
10744295,
10744328,
10744507,
10744540,
10744580,
10744634,
10745682,
10745798,
10746156,
10746174,
10746327,
10746541,
10746571,
10746724,
10746767,
10746835,
10747079,
10747130,
10747285,
10747435,
10747446,
10747615,
10747639,
10747668,
10747675,
10747832,
10747841,
10747914,
10747960,
10748050,
10748092,
10748139,
10748220,
10748235,
10748245,
10748312,
10748480,
10748521,
10748556,
10748592,
10748618,
10748722,
10748754,
10749908,
10749978,
10750049,
10750100,
10750162,
10750234,
10750250,
10750258,
10750271,
10750295,
10750472,
10750510,
10750555,
10750603,
10750639,
10750654,
10750898,
10750922,
10750937,
10750971,
10751015,
10751038,
10751082,
10751153,
10751176,
10751232,
10751270,
10751303,
10751442,
10751448,
10751493,
10751523,
10751538,
10751662,
10751725,
10751871,
10751887,
10751938,
10752016,
10752024,
10752036,
10752071,
10752100,
10752221,
10752775,
10752821,
10752846,
10753013,
10753105,
10753133,
10753139,
10753248,
10753329,
10753337,
10753417,
10753437,
10753456,
10753575,
10753577,
10753591,
10753656,
10753710,
10753721,
10753810,
10753952,
10754036,
10754057,
10754081,
10754241,
10754271,
10754410,
10754542,
10754570,
10754592,
10754596,
10754684,
10754718,
10754736,
10754828,
10754878,
10754965,
10756961,
10757050,
10757130,
10757157,
10757288,
10757379,
10757419,
10757576,
10757687,
10757764,
10757858,
10757978,
10757993,
10758104,
10758128,
10758217,
10758422,
10758509,
10758637,
10758648,
10758805,
10758837,
10758945,
10758969,
10759076,
10759082,
10759175,
10759380,
10759537,
10759626,
10759648,
10759694,
10759736,
10759742,
10759772,
10759799,
10759837,
10760057,
10760339,
10760651,
10760745,
10760811,
10760919,
10761205,
10761338,
10761370,
10761406,
10761470,
10761513,
10761521,
10761736,
10761848,
10764676,
10764683,
10764705,
10764711,
10764759,
10764804,
10765250,
10765283,
10765348,
10765363,
10765383,
10765494,
10765652,
10765686,
10765746,
10765859,
10765912,
10765940,
10765957,
10766029,
10766091,
10766149,
10766286,
10766335,
10766383,
10766475,
10766581,
10766769,
10766777,
10766783,
10766957,
10767013,
10767114,
10767192,
10767263,
10767290,
10767340,
10767417,
10767679,
10767692,
10767769,
10767794,
10767834,
10767856,
10767859,
10767906,
10768403,
10768851,
10768942,
10768998,
10769210,
10769296,
10769439,
10769456,
10769644,
10769758,
10769877,
10769888,
10769956,
10770029,
10770045,
10770128,
10770177,
10770407,
10770561,
10770575,
10770589,
10770682,
10770687,
10770870,
10770950,
10770985,
10771113,
10771145,
10771967,
10772269,
10772320,
10772354,
10773327,
10773472,
10773479,
10773628,
10773758,
10773908,
10774082,
10774116,
10774185,
10774334,
10774755,
10774779,
10774800,
10774837,
10774853,
10779137,
10779282,
10779554,
10779706,
10780126,
10780186,
10780318,
10780342,
10780358,
10780403,
10780432,
10780667,
10780728,
10780829,
10780859,
10781067,
10781086,
10781114,
10781188,
10781798,
10781806,
10781889,
10782072,
10782217,
10782375,
10782569,
10782656,
10783090,
10783300,
10783325,
10783392,
10783441,
10783477,
10783504,
10783737,
10783823,
10783865,
10783927,
10783966,
10783972,
10783990,
10784010,
10784993,
10784997,
10785439,
10785472,
10785478,
10785550,
10785614,
10785728,
10786068,
10786092,
10786396,
10786700,
10786716,
10786766,
10786817,
10786868,
10786952,
10786996,
10787059,
10787242,
10787760,
10787785,
10787794,
10787823,
10787848,
10788047,
10788129,
10788186,
10788270,
10788314,
10788539,
10788560,
10788581,
10788660,
10788712,
10788840,
10788873,
10788964,
10789133,
10789160,
10789230,
10789239,
10789288,
10789317,
10789752,
10789760,
10789839,
10790045,
10790422,
10790557,
10790646,
10790703,
10790827,
10791081,
10791393,
10791599,
10791732,
10791778,
10791857,
10791889,
10793055,
10793087,
10793372,
10793435,
10793859,
10793868,
10793998,
10794050,
10794298,
10794318,
10794398,
10794469,
10794496,
10794501,
10794620,
10794677,
10794759,
10795320,
10795358,
10795408,
10795425,
10795468,
10795567,
10795588,
10795608,
10795872,
10795904,
10795977,
10796001,
10796012,
10796039,
10796097,
10796183,
10796317,
10796326,
10796347,
10796359,
10796391,
10797968,
10798285,
10798589,
10798731,
10798819,
10799706,
10799773,
10799878,
10799941,
10800138,
10800280,
10800387,
10800484,
10800592,
10800639,
10800658,
10801478,
10801531,
10801634,
10801666,
10801711,
10801782,
10801833,
10801845,
10801894,
10801975,
10802053,
10802264,
10802300,
10802486,
10802540,
10802913,
10803014,
10803038,
10803080,
10803157,
10803173,
10803291,
10803349,
10803365,
10803399,
10803420,
10803463,
10803599,
10803717,
10803727,
10803758,
10803845,
10803909,
10803953,
10804033,
10804610,
10804634,
10804638,
10804658,
10804669,
10804744,
10804750,
10804861,
10804902,
10804910,
10805037,
10805147,
10805227,
10805335,
10805343,
10805371,
10805435,
10805440,
10805502,
10805506,
10805578,
10805699,
10805849,
10805914,
10806079,
10806170,
10807539,
10807710,
10807783,
10840243,
10840393,
10840473,
10840498,
10840517,
10840596,
10840832,
10840896,
10841468,
10841475,
10841573,
10842606,
10842637,
10842886,
10843287,
10843419,
10843664,
10844055,
10844285,
10844328,
10844518,
10844669,
10844936,
10845013,
10845044,
10845408,
10845692,
10845915,
10846112,
10846143,
10846317,
10846425,
10846444,
10846538,
10846729,
10846772,
10846826,
10846961,
10847040,
10847087,
10847157,
10847160,
10847327,
10847501,
10847716,
10847729,
10847883,
10848022,
10848132,
10848684,
10848999,
10849236,
10849627,
10849643,
10851197,
10851212,
10851340,
10851729,
10851800,
10851912,
10852655,
10852673,
10852690,
10852757,
10852808,
10852955,
10853088,
10853170,
10854063,
10854259,
10854472,
10854536,
10854562,
10854591,
10854677,
10854719,
10854768,
10854876,
10854972,
10855039,
10855063,
10855078,
10855137,
10855583,
10855803,
10855833,
10856155,
10856224,
10856350,
10856364,
10856376,
10856445,
10856495,
10856514,
10856668,
10856699,
10856752,
10856773,
10856845,
10856961,
10857092,
10857122,
10857240,
10857252,
10857299,
10857317,
10857366,
10857484,
10857617,
10857655,
10857671,
10857680,
10857687,
10857729,
10857732,
10857746,
10857753,
10857821,
10857887,
10857917,
10857940,
10858007,
10858137,
10858179,
10858261,
10858762,
10858811,
10858832,
10858906,
10858919,
10858923,
10858960,
10858978,
10859042,
10859073,
10859080,
10859095,
10859120,
10859128,
10859132,
10859135,
10859183,
10859193,
10859200,
10859261,
10859278,
10859293,
10859299,
10859302,
10859356,
10859364,
10859393,
10859491,
10859981,
10860049,
10860129,
10860172,
10860208,
10860216,
10860222,
10860283,
10860290,
10860564,
10860605,
10860696,
10860817,
10860904,
10860986,
10861003,
10861017,
10861025,
10861028,
10861034,
10861125,
10861219,
10861239,
10861464,
10861643,
10861829,
10861898,
10861998,
10862130,
10862510,
10862762,
10862771,
10862812,
10862814,
10862856,
10862889,
10862918,
10862979,
10863021,
10863087,
10863256,
10863582,
10863688,
10863741,
10863977,
10864094,
10864221,
10864248,
10864298,
10864308,
10864532,
10865113,
10865125,
10865174,
10865226,
10865254,
10865312,
10865367,
10866302,
10866331,
10866389,
10866496,
10866552,
10866566,
10866619,
10866661,
10867537,
10867540,
10867619,
10867630,
10867960,
10868001,
10868146,
10868347,
10868438,
10868553,
10868614,
10868815,
10868978,
10869056,
10869059,
10870196,
10870358,
10870460,
10870624,
10870651,
10870698,
10871001,
10871068,
10871226,
10871290,
10871350,
10871360,
10871971,
10872173,
10872206,
10872295,
10872317,
10872373,
10873002,
10873188,
10873375,
10873517,
10874338,
10874461,
10874636,
10874702,
10874835,
10874961,
10874987,
10875082,
10875094,
10875362,
10875661,
10875775,
10875963,
10876095,
10876201,
10876779,
10876837,
10876911,
10877000,
10877013,
10877135,
10877460,
10877501,
10877710,
10877726,
10877751,
10877787,
10877809,
10878116,
10878121,
10878244,
10878489,
10878577,
10878798,
10879027,
10879039,
10879314,
10879335,
10879391,
10879408,
10880901,
10881022,
10881459,
10881594,
10881600,
10881853,
10881876,
10881966,
10882059,
10882125,
10882311,
10882326,
10882365,
10882473,
10882489,
10882823,
10883678,
10883690,
10883921,
10883965,
10883993,
10884100,
10884307,
10884472,
10884532,
10884561,
10884879,
10884923,
10884951,
10884975,
10885159,
10885236,
10885299,
10885307,
10885407,
10886548,
10886587,
10886736,
10886739,
10886774,
10886800,
10886829,
10886995,
10887649,
10887849,
10887943,
10887990,
10888040,
10888175,
10889397,
10889515,
10889990,
10890455,
10890498,
10890578,
10890965,
10891045,
10891057,
10891124,
10891842,
10891943,
10891950,
10891971,
10891974,
10892161,
10892391,
10892605,
10892622,
10892655,
10892849,
10892857,
10892955,
10892998,
10893050,
10893058,
10893677,
10893700,
10893718,
10893979,
10894062,
10894109,
10894120,
10894224,
10894242,
10894410,
10894602,
10894612,
10894681,
10894812,
10894971,
10895037,
10895235,
10895294,
10895332,
10895588,
10896479,
10896507,
10896559,
10896903,
10897407,
10897801,
10897924,
10898001,
10898053,
10898062,
10898118,
10898861,
10898893,
10898904,
10899168,
10899241,
10899441,
10899489,
10899607,
10899636,
10899745,
10899875,
10899978,
10900002,
10900023,
10900907,
10901163,
10901679,
10901685,
10901851,
10901868,
10902087,
10902135,
10902300,
10902357,
10902444,
10902512,
10902628,
10902658,
10902802,
10903239,
10903247,
10903254,
10903338,
10903393,
10903569,
10903671,
10903781,
10903835,
10904026,
10904079,
10904083,
10904148,
10904184,
10904332,
10904343,
10904427,
10904455,
10904461,
10904480,
10904540,
10904543,
10904586,
10904786,
10904907,
10905306,
10905462,
10905717,
10905964,
10905970,
10906210,
10906237,
10906261,
10906271,
10906308,
10907028,
10907328,
10907503,
10907648,
10907797,
10907823,
10907988,
10908160,
10908199,
10908519,
10908674,
10908734,
10908846,
10908849,
10908861,
10908997,
10910088,
10910183,
10910291,
10910303,
10911246,
10911455,
10911527,
10911719,
10911851,
10911929,
10911936,
10911981,
10912607,
10913022,
10913524,
10913747,
10913939,
10914108,
10914245,
10914249,
10914299,
10914303,
10914320,
10914335,
10914370,
10914485,
10914998,
10915087,
10915235,
10915430,
10915447,
10915487,
10915526,
10915574,
10915998,
10916198,
10916769,
10916791,
10917095,
10917607,
10917658,
10917666,
10917674,
10917736,
10917745,
10917790,
10917838,
10918104,
10918260,
10918466,
10918614,
10918815,
10918988,
10919235,
10919256,
10919372,
10919620,
10919671,
10919837,
10919888,
10920067,
10920100,
10920113,
10920248,
10920288,
10920460,
10920497,
10920509,
10920529,
10921003,
10921065,
10921131,
10921533,
10921689,
10921700,
10921995,
10922057,
10922200,
10922228,
10922432,
10922492,
10922554,
10922558,
10922719,
10922762,
10922990,
10923194,
10923197,
10923341,
10923381,
10923389,
10923439,
10923528,
10923554,
10923607,
10923626,
10923801,
10923972,
10924311,
10924643,
10924658,
10924702,
10924707,
10924876,
10925052,
10925063,
10925070,
10925222,
10925274,
10925311,
10925386,
10925417,
10925507,
10925555,
10925699,
10925724,
10925793,
10925820,
10926028,
10926378,
10926392,
10926395,
10926423,
10926436,
10926543,
10926580,
10926712,
10926803,
10926849,
10926865,
10926889,
10926909,
10926979,
10927022,
10927138,
10927233,
10927254,
10928083,
10928293,
10928337,
10928805,
10928836,
10929128,
10929154,
10929175,
10929202,
10929269,
10929436,
10929582,
10929764,
10929777,
10930042,
10930060,
10930143,
10930193,
10930258,
10930298,
10930302,
10930460,
10930465,
10930636,
10931073,
10931307,
10931550,
10931554,
10931958,
10931980,
10932163,
10932213,
10932457,
10932531,
10932790,
10932872,
10932880,
10933350,
10933459,
10933551,
10933568,
10933670,
10933801,
10933843,
10933847,
10934314,
10934680,
10934758,
10934929,
10934949,
10935119,
10935585,
10935795,
10936043,
10936322,
10936750,
10936846,
10936868,
10937217,
10937392,
10937557,
10937702,
10937793,
10937916,
10938016,
10938221,
10938368,
10938419,
10938613,
10938777,
10938869,
10939217,
10939407,
10939416,
10939443,
10939522,
10939583,
10939612,
10939710,
10939719,
10939879,
10939902,
10939940,
10939947,
10939982,
10940043,
10940047,
10940171,
10940288,
10940350,
10940436,
10940830,
10941051,
10941225,
10941418,
10941601,
10941618,
10941669,
10941702,
10941710,
10941866,
10941914,
10942025,
10942086,
10942310,
10942478,
10942521,
10943411,
10943531,
10943543,
10943665,
10943709,
10943907,
10943929,
10943993,
10944025,
10944095,
10944283,
10944696,
10944705,
10944759,
10944809,
10945090,
10945352,
10945363,
10945652,
10946081,
10946263,
10946354,
10946500,
10947046,
10947178,
10947828,
10947977,
10948010,
10950037,
10950122,
10950224,
10950252,
10950424,
10951440,
10954346,
10954654,
10955030,
10955507,
10955672,
10955777,
10955846,
10956033,
10956114,
10956267,
10956350,
10956407,
10956520,
10956558,
10956606,
10956738,
10957593,
10957632,
10959364,
10959506,
10959531,
10959536,
10959676,
10959706,
10959865,
10960050,
10960117,
10960205,
10960578,
10960661,
10960922,
10961269,
10961295,
10961372,
10961412,
10961474,
10961533,
10961569,
10961763,
10961872,
10961932,
10961957,
10961983,
10962601,
10962687,
10962734,
10962876,
10962983,
10963055,
10963565,
10963814,
10964298,
10964483,
10964644,
10964760,
10964963,
10965102,
10965370,
10965384,
10965391,
10965394,
10965450,
10965453,
10965528,
10965649,
10965654,
10966317,
10966410,
10982478,
10982877,
10982901,
10982920,
10982937,
10982964,
10983139,
10983141,
10983187,
10983261,
10983321,
10983348,
10983746,
10983752,
10983836,
10984040,
10984102,
10984120,
10984160,
10984325,
10984328,
10984396,
10984411,
10984425,
10984454,
10984668,
10984768,
10984783,
10984855,
10984883,
10984922,
10985004,
10985061,
10985106,
10985119,
10985144,
10985706,
10985733,
10985897,
10986226,
10986246,
10986720,
10986728,
10986784,
10986799,
10987518,
10987649,
10987715,
10988299,
10988432,
10988560,
10988594,
10988704,
10988983,
10989171,
10989262,
10989648,
10989749,
10989947,
10990368,
10990382,
10990494,
10991406,
10991553,
10991668,
10991710,
10993267,
10993600,
10997066,
10997364,
10997686,
10998106,
10998626,
10999633,
10999901,
11000743,
11001795,
11001819,
11002447,
11002701,
11002795,
11003678,
11004134,
11005049,
11009375,
11010976,
11018797,
11020543,
11020850,
11021012,
11024534,
11024803,
11025326,
11025500,
11026056,
11026522,
11026570,
11035388,
11035510,
11035988,
11036223,
11036717,
11039694,
11040014,
11040343,
11040708,
11040849,
11045547,
11045565,
11045764,
11046262,
11046647,
11046782,
11047284,
11048142,
11048894,
11049703,
11058211,
11058438,
11064335,
11065290,
11065468,
11065916,
11072619,
11073451,
11074113,
11074628,
11078149,
11078400,
11080650,
11081004,
11082106,
11083199,
11085547,
11089151,
11090218,
11090282,
11093425,
11094646,
11100284,
11101584,
11104394,
11104778,
11106164,
11106831,
11107283,
11109329,
11110470,
11110485,
11111146,
11111360,
11111368,
11112054,
11112563,
11114079,
11114299,
11115242,
11115595,
11116497,
11117535,
11117996,
11122024,
11122661,
11123260,
11123873,
11125062,
11125434,
11125660,
11126155,
11127492,
11127502,
11132125,
11137246,
11153446,
11158787,
11159101,
11159333,
11159469,
11162829,
11169311,
11169548,
11170768,
11173273,
11177039,
11178197,
11178684,
11179187,
11179428,
11181357,
11181868,
11185639,
11186574,
11193154,
11193164,
11193388,
11194615,
11195122,
11195134,
11195797,
11203400,
11203572,
11203585,
11203909,
11206511,
11207219,
11207287,
11211945,
11212222,
11212527,
11212838,
11212873,
11213720,
11216484,
11216962,
11217212,
11217436,
11217756,
11228709,
11230116,
11230962,
11231244,
11232564,
11232570,
11232872,
11233117,
11233228,
11233297,
11233448,
11233793,
11233803,
11233914,
11233956,
11234006,
11236861,
11237803,
11238123,
11238456,
11240050,
11240091,
11240270,
11240326,
11240559,
11240685,
11240888,
11241436,
11241974,
11242146,
11242150,
11245116,
11245733,
11246501,
11246731,
11246773,
11246890,
11247572,
11247712,
11247785,
11247802,
11248286,
11248587,
11248620,
11250716,
11250840,
11251919,
11252281,
11252504,
11252667,
11253188,
11253306,
11253845,
11253918,
11254028,
11254041,
11255040,
11256742,
11257258,
11257334,
11257380,
11257856,
11258288,
11258511,
11258734,
11259033,
11259364,
11259493,
11259551,
11259761,
11260391,
11260477,
11260966,
11261312,
11261442,
11261455,
11261557,
11261565,
11261592,
11261777,
11262052,
11262285,
11262365,
11262882,
11262930,
11263406,
11263417,
11263495,
11264424,
11264459,
11264512,
11264831,
11264849,
11264909,
11264972,
11265194,
11265335,
11265479,
11265561,
11265578,
11265619,
11265674,
11266109,
11266229,
11266266,
11266304,
11266382,
11266555,
11266612,
11266833,
11268543,
11268626,
11268769,
11269135,
11269239,
11269303,
11269605,
11269783,
11269816,
11271284,
11271401,
11271459,
11271633,
11271881,
11271932,
11271970,
11272181,
11273419,
11273545,
11273917,
11274449,
11274492,
11274607,
11274751,
11275368,
11275479,
11275510,
11275666,
11275751,
11275863,
11276258,
11276781,
11277045,
11277366,
11278157,
11278259,
11278413,
11278575,
11279836,
11279861,
11279969,
11280255,
11280866,
11280958,
11281097,
11281338,
11281623,
11281789,
11281904,
11281948,
11282047,
11282137,
11282645,
11282694,
11282787,
11282829,
11283913,
11284027,
11284194,
11284537,
11284705,
11284726,
11284863,
11284924,
11284935,
11285251,
11285850,
11285895,
11286190,
11290404,
11290672,
11290964,
11291004,
11291145,
11291254,
11291264,
11291540,
11291985,
11292051,
11292134,
11292149,
11292217,
11292223,
11292304,
11292320,
11292387,
11292463,
11292546,
11292633,
11292787,
11293400,
11293439,
11294179,
11294316,
11294510,
11294905,
11295370,
11295620,
11295739,
11296309,
11297021,
11297766,
11298146,
11304695,
11304758,
11304826,
11305227,
11311558,
11311622,
11311641,
11312379,
11312415,
11312488,
11312640,
11314429,
11314455,
11315472,
11315556,
11317023,
11317318,
11317360,
11317400,
11318388,
11318843,
11319744,
11319825,
11320321,
11320455,
11321391,
11321471,
11321904,
11322283,
11322322,
11322392,
11323094,
11323636,
11324267,
11324409,
11324663,
11325038,
11325140,
11325226,
11325906,
11325962,
11325995,
11326467,
11326617,
11326638,
11327926,
11327999,
11328406,
11328548,
11328600,
11328966,
11329138,
11329260,
11330020,
11331288,
11335141,
11335570,
11337436,
11338634,
11338764,
11339895,
11342972,
11343977,
11345105,
11346042,
11346325,
11347708,
11347929,
11350035,
11352901,
11353757,
11355021,
11355053,
11355122,
11356325,
11358664,
11359355,
11359917,
11360198,
11361830,
11361852,
11362229,
11362249,
11366516,
11366595,
11366835,
11368384,
11368393,
11368634,
11369061,
11369571,
11370016,
11371110,
11371228,
11372046,
11377508,
11377809,
11378362,
11382476,
11386749,
11387889,
11388548,
11390012,
11390704,
11391298,
11392075,
11392638,
11392680,
11396806,
11396960,
11406819,
11407218,
11407242,
11409152,
11410428,
11411973,
11413312,
11416744,
11417303,
11417842,
11418702,
11419695,
11422584,
11423475,
11425875,
11425948,
11428517,
11432296,
11438103,
11438117,
11438982,
11442507,
11450253,
11452697,
11453042,
11453308,
11453909,
11455614,
11455750,
11458727,
11459308,
11460034,
11460120,
11460551,
11460583,
11460616,
11460957,
11461018,
11461069,
11463418,
11463649,
11463866,
11464139,
11464154,
11465289,
11468687,
11469205,
11470109,
11470664,
11470694,
11470713,
11471381,
11479223,
11479676,
11481355,
11502998,
11505874,
11516085,
11517416,
11523329,
11524133,
11524590,
11525773,
11526526,
11527036,
11527069,
11527534,
11542519,
11542583,
11543621,
11544379,
11544880,
11584278,
11585193,
11585431,
11585481,
11632903,
11663868,
11665021,
11671692,
11673736,
11673750,
11673807,
11676775,
11678625,
11680974,
11682335,
11684396,
11684486,
11688273,
11698348,
11703691,
11703718,
11703732,
11703791,
11703847,
11703879,
11704034,
11704200,
11704248,
11704280,
11704629,
11705363,
11705417,
11705548,
11705741,
11705912,
11706134,
11706287,
11706312,
11706661,
11706765,
11707037,
11707341,
11708248,
11711330,
11711930,
11712210,
11712381,
11712688,
11712782,
11712879,
11712985,
11720043,
11724276,
11724314,
11733829,
11737761,
11740186,
11741263,
11742418,
11745674,
11747111,
11747218,
11747398,
11748452,
11748643,
11748716,
11830567,
11830608,
11831227,
11831295,
11831846,
11832330,
11833012,
11833192,
11834417,
11853778,
11862285,
11875537,
11900614,
11910662,
11913811,
11914469,
11914933,
11915595,
11916109,
11917989);
	}

	foreach($order_ids as $order_id){
		QLOG::LOG("order_id :".$order_id." start... ");
		$sql = "SELECT orl.order_sn, orl.order_id
	    		FROM ecshop.order_relation orl
	    			INNER JOIN ecshop.ecs_order_goods og ON og.order_id = orl.order_id
	    			INNER JOIN romeo.inventory_item_detail iid ON iid.order_goods_id = convert(og.rec_id using utf8)
	    			INNER JOIN romeo.inventory_transaction it ON iid.inventory_transaction_id = it.inventory_transaction_id 
	    				AND it.inventory_transaction_type_id IN ('ITT_SO_RET','ITT_SO_CANCEL','ITT_SO_REJECT')
	    		WHERE orl.parent_order_id = '{$order_id}'";
	    $in_condition = $db->getAll($sql);
	    if(!empty($in_condition)){
	    	QLOG::LOG($order_id.' have -t inventory_in ');
	    	continue;
	    }
	    
		$sql = "SELECT IFNULL(gs.barcode,g.barcode) as og_barcode,og.goods_number from ecshop.ecs_order_goods og 
			LEFT JOIN ecshop.ecs_goods_style gs on gs.goods_id = og.goods_id and gs.is_delete=0
			LEFT JOIN ecshop.ecs_goods g on g.goods_id = og.goods_id
			where og.order_id = '{$order_id}' 
			group by og.rec_id ";
		$goods_infos = $db->getAll($sql);
	
		$goods_arr = array();
		foreach($goods_infos as $key=>$goods_info){
			$og_barcode = array();
			for($i = 0;$i<$goods_info['goods_number'];$i++){
				$og_barcode[$key."_".$i] = 'new';
			}
			
			$goods_arr[$goods_info['og_barcode']] = $og_barcode;
		}
		if(empty($goods_arr)){
	    	QLOG::LOG('order_id : '.$order_id.' is empty goods info ! ');
	    	continue;
	    }
		$lock_name = 'reject_in';
	    require_once('includes/lib_filelock.php');
	    
	    if (!wait_file_lock($lock_name, 10)) {
	        QLOG::LOG('time_out.please check there is anyothers who is doing reject_in ');
	        die('time_out.please check there is anyothers who is doing reject_in');
	    }
	    create_file_lock($lock_name);
	    require_once('includes/lib_order.php');
		$t_order_id =0;
		$t_order_id = generate_return_all_back_order($order_id,'reject',$goods_arr);
		if($t_order_id > 0){
	    	release_file_lock($lock_name);
	    	$sql = "insert into ecshop.ecs_order_action(order_id,action_user,action_time,action_note,note_type) values('".$order_id."','ytchen',now(),'菜鸟订单转仓,重复发货,已经操作系统拒收入库','SHIPPING')";
	    	$db->query($sql);
	    	QLOG::LOG("order_id :".$order_id." end... ");
	    	continue;
	    }else{
	    	release_file_lock($lock_name);
	    	QLOG::LOG("fail_reject .please to ERP info --(order_id:".$order_id.") ");
	    	continue;
	    }
	    
	}
	print 'over_reject_id_orders';

}
?>