<?php
class GP_Locale {
	var $english_name;
	var $native_name;
	var $text_direction = 'ltr';
	var $lang_code_iso_639_1;
	var $country_code;
	var $wp_locale;
	var $slug;
	var $nplurals = 2;
	var $plural_expression = 'n != 1';
	// TODO: days, months, decimals, quotes
	
	function combined_name() {
		/* translators: combined name for locales: 1: name in English, 2: native name */
		return sprintf( _x( '%1$s/%2$s', 'locales' ), $this->english_name, $this->native_name );
	}
	
	function numbers_for_index( $index, $how_many = 3, $test_up_to = 1000 ) {
		$expression = Gettext_Translations::parenthesize_plural_exression( $this->plural_expression );
		$index_from_number = Gettext_Translations::make_plural_form_function( $this->nplurals, $expression );
		$numbers = array();
		for( $number = 0; $number < $test_up_to; ++$number ) {
			if ( $index_from_number( $number ) == $index ) {
				$numbers[] = $number;
				if ( count( $numbers ) >= $how_many ) break;
			}
		}
		return $numbers;
	}
}

class GP_Locales {
	
	var $locales = array();
	
	function GP_Locales() {
		$en = new GP_Locale();
		$en->english_name = 'English';
		$en->native_name = 'English';
		$en->lang_code_iso_639_1 = 'en';
		$en->country_code = 'us';
		$en->wp_locale = 'en_US';
		$en->slug = 'en';

		$bg = new GP_Locale();
		$bg->english_name = 'Bulgarian';
		$bg->native_name = 'Български';
		$bg->lang_code_iso_639_1 = 'bg';
		$bg->country_code = 'bg';
		$bg->wp_locale = 'bg_BG';
		$bg->slug = 'bg';

		$es = new GP_Locale();
		$es->english_name = 'Spanish';
		$es->native_name = 'Español';
		$es->lang_code_iso_639_1 = 'es';
		$es->country_code = 'es';
		$es->wp_locale = 'es_ES';
		$es->slug = 'es';

		$de = new GP_Locale();
		$de->english_name = 'German';
		$de->native_name = 'Deutsch';
		$de->lang_code_iso_639_1 = 'de';
		$de->country_code = 'de';
		$de->wp_locale = 'de_DE';
		$de->slug = 'de';
		
		$fr = new GP_Locale();
		$fr->english_name = 'French';
		$fr->native_name = 'Français';
		$fr->lang_code_iso_639_1 = 'fr';
		$fr->country_code = 'fr';
		$fr->wp_locale = 'fr_FR';
		$fr->slug = 'fr';
		
		$pt = new GP_Locale();
		$pt->english_name = 'Portuguese';
		$pt->native_name = 'Português';
		$pt->lang_code_iso_639_1 = 'pt';
		$pt->country_code = 'pt';
		$pt->wp_locale = 'pt_PT';
		$pt->slug = 'pt';

		$he = new GP_Locale();
		$he->english_name = 'Hebrew';
		$he->native_name = 'עִבְרִית';
		$he->lang_code_iso_639_1 = 'he';
		$he->country_code = 'il';
		$he->wp_locale = 'he_IL';
		$he->slug = 'he';
		$he->rtl = true;

		$ja = new GP_Locale();
		$ja->english_name = 'Japanese';
		$ja->native_name = '日本語';
		$ja->lang_code_iso_639_1 = 'ja';
		$ja->country_code = 'jp';
		$ja->wp_locale = 'ja';
		$ja->slug = 'ja';
		$ja->nplurals = 1;
		$ja->plural_expression = '0';

		$ar = new GP_Locale();
		$ar->english_name = 'Arabic';
		$ar->native_name = 'العربية';
		$ar->lang_code_iso_639_1 = 'ar';
		$ar->country_code = '';
		$ar->wp_locale = 'ar';
		$ar->slug = 'ar';
		$ar->nplurals = 6;
		$ar->plural_expression = 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5';

		$af = new GP_Locale();
		$af->english_name = 'Afrikaans';
		$af->native_name = 'Afrikaans';
		$af->lang_code_iso_639_1 = 'af';
		$af->country_code = 'za';
		$af->wp_locale = 'af';
		$af->slug = 'af';

		$am = new GP_Locale();
		$am->english_name = 'Armenian';
		$am->native_name = 'հայերեն';
		$am->lang_code_iso_639_1 = 'hy';
		$am->lang_code_iso_639_2 = 'hye';
		$am->country_code = 'am';
		$am->slug = 'am';

		$af = new GP_Locale();
		$af->english_name = 'Asturian';
		$af->native_name = 'Asturianu';
		$af->lang_code_iso_639_1 = null;
		$af->lang_code_iso_639_2 = 'ast';
		$af->country_code = 'es';
		$af->slug = 'ast';

		$az = new GP_Locale();
		$az->english_name = 'Azerbaijani';
		$az->native_name = 'Azərbaycan dili';
		$az->lang_code_iso_639_1 = 'az';
		$az->lang_code_iso_639_2 = 'aze';
		$az->country_code = 'az';
		$az->slug = 'az';

		$az = new GP_Locale();
		$az->english_name = 'Azerbaijani';
		$az->native_name = 'Azərbaycan dili';
		$az->lang_code_iso_639_1 = 'az';
		$az->lang_code_iso_639_2 = 'aze';
		$az->country_code = 'az';
		$az->slug = 'az';

		$be = new GP_Locale();
		$be->english_name = 'Belarusian';
		$be->native_name = 'Беларуская мова';
		$be->lang_code_iso_639_1 = 'be';
		$be->lang_code_iso_639_2 = 'bel';
		$be->country_code = 'by';
		$be->slug = 'be';

		$bm = new GP_Locale();
		$bm->english_name = 'Bambara';
		$bm->native_name = 'Bamanankan';
		$bm->lang_code_iso_639_1 = 'bm';
		$bm->lang_code_iso_639_2 = 'bam';
		$bm->country_code = '';
		$bm->slug = 'bm';

		$bo = new GP_Locale();
		$bo->english_name = 'Tibetan';
		$bo->native_name = 'བོད་སྐད';
		$bo->lang_code_iso_639_1 = 'bo';
		$bo->lang_code_iso_639_2 = 'tib';
		$bo->country_code = '';
		$bo->slug = 'bo';

		$bs = new GP_Locale();
		$bs->english_name = 'Bosnian';
		$bs->native_name = 'Bosanski';
		$bs->lang_code_iso_639_1 = 'bs';
		$bs->lang_code_iso_639_2 = 'bos';
		$bs->country_code = 'ba';
		$bs->slug = 'bs';

		$bs = new GP_Locale();
		$bs->english_name = 'Bosnian';
		$bs->native_name = 'Bosanski';
		$bs->lang_code_iso_639_1 = 'bs';
		$bs->lang_code_iso_639_2 = 'bos';
		$bs->country_code = 'ba';
		$bs->slug = 'bs';

		$ca = new GP_Locale();
		$ca->english_name = 'Catalan';
		$ca->native_name = 'Català';
		$ca->lang_code_iso_639_1 = 'ca';
		$ca->lang_code_iso_639_2 = 'cat';
		$ca->country_code = '';
		$ca->slug = 'ca';

		$ce = new GP_Locale();
		$ce->english_name = 'Chechen';
		$ce->native_name = 'Нохчийн мотт';
		$ce->lang_code_iso_639_1 = 'ce';
		$ce->lang_code_iso_639_2 = 'che';
		$ce->country_code = '';
		$ce->slug = 'ce';

		$cs = new GP_Locale();
		$cs->english_name = 'Czech';
		$cs->native_name = 'čeština';
		$cs->lang_code_iso_639_1 = 'cs';
		$cs->lang_code_iso_639_2 = 'ces';
		$cs->country_code = 'cz';
		$cs->slug = 'cs';

		$csb = new GP_Locale();
		$csb->english_name = 'Kashubian';
		$csb->native_name = 'Kaszëbsczi';
		$csb->lang_code_iso_639_1 = null;
		$csb->lang_code_iso_639_2 = 'csb';
		$csb->country_code = '';
		$csb->slug = 'csb';

		$csb = new GP_Locale();
		$csb->english_name = 'Kashubian';
		$csb->native_name = 'Kaszëbsczi';
		$csb->lang_code_iso_639_1 = null;
		$csb->lang_code_iso_639_2 = 'csb';
		$csb->country_code = '';
		$csb->slug = 'csb';

		$cy = new GP_Locale();
		$cy->english_name = 'Welsh';
		$cy->native_name = 'Cymraeg';
		$cy->lang_code_iso_639_1 = 'cy';
		$cy->lang_code_iso_639_2 = 'cym';
		$cy->country_code = 'uk';
		$cy->slug = 'cy';

		$da = new GP_Locale();
		$da->english_name = 'Danish';
		$da->native_name = 'Dansk';
		$da->lang_code_iso_639_1 = 'da';
		$da->lang_code_iso_639_2 = 'dan';
		$da->country_code = 'dk';
		$da->slug = 'da';

		$el_po = new GP_Locale();
		$el_po->english_name = 'Polytonic Greek';
		$el_po->native_name = 'Polytonic Greek'; // TODO
		$el_po->lang_code_po_iso_639_1 = null;
		$el_po->lang_code_po_iso_639_2 = null;
		$el_po->country_code = 'gr';
		$el_po->slug = 'el-po';

		$el = new GP_Locale();
		$el->english_name = 'Greek';
		$el->native_name = 'Ελληνικά';
		$el->lang_code_iso_639_1 = 'el';
		$el->lang_code_iso_639_2 = 'ell';
		$el->country_code = 'gr';
		$el->slug = 'el';

		$eo = new GP_Locale();
		$eo->english_name = 'Esperanto';
		$eo->native_name = 'Esperanto';
		$eo->lang_code_iso_639_1 = 'eo';
		$eo->lang_code_iso_639_2 = 'epo';
		$eo->country_code = '';
		$eo->slug = 'eo';
		
		$et = new GP_Locale();
		$et->english_name = 'Estonian';
		$et->native_name = 'Eesti';
		$et->lang_code_iso_639_1 = 'et';
		$et->lang_code_iso_639_2 = 'est';
		$et->country_code = 'ee';
		$et->slug = 'et';

		$eu = new GP_Locale();
		$eu->english_name = 'Basque';
		$eu->native_name = 'Euskara';
		$eu->lang_code_iso_639_1 = 'eu';
		$eu->lang_code_iso_639_2 = 'eus';
		$eu->country_code = 'es';
		$eu->slug = 'eu';

		$fa = new GP_Locale();
		$fa->english_name = 'Persian';
		$fa->native_name = 'فارسی';
		$fa->lang_code_iso_639_1 = 'fa';
		$fa->lang_code_iso_639_2 = 'fas';
		$fa->country_code = '';
		$fa->slug = 'fa';

		$fi = new GP_Locale();
		$fi->english_name = 'Finnish';
		$fi->native_name = 'Suomi';
		$fi->lang_code_iso_639_1 = 'fi';
		$fi->lang_code_iso_639_2 = 'fin';
		$fi->country_code = 'fi';
		$fi->slug = 'fi';

		$ga = new GP_Locale();
		$ga->english_name = 'Irish';
		$ga->native_name = 'Gaelige';
		$ga->lang_code_iso_639_1 = 'ga';
		$ga->lang_code_iso_639_2 = 'gle';
		$ga->country_code = 'ie';
		$ga->slug = 'ga';

		$gl = new GP_Locale();
		$gl->english_name = 'Galician';
		$gl->native_name = 'Galego';
		$gl->lang_code_iso_639_1 = 'gl';
		$gl->lang_code_iso_639_2 = 'glg';
		$gl->country_code = 'es';
		$gl->slug = 'gl';

		$gl = new GP_Locale();
		$gl->english_name = 'Galician';
		$gl->native_name = 'Galego';
		$gl->lang_code_iso_639_1 = 'gl';
		$gl->lang_code_iso_639_2 = 'glg';
		$gl->country_code = 'es';
		$gl->slug = 'gl';

		$hr = new GP_Locale();
		$hr->english_name = 'Croatian';
		$hr->native_name = 'Hrvatski';
		$hr->lang_code_iso_639_1 = 'hr';
		$hr->lang_code_iso_639_2 = 'hrv';
		$hr->country_code = 'hr';
		$hr->slug = 'hr';

		$hu = new GP_Locale();
		$hu->english_name = 'Hungarian';
		$hu->native_name = 'Magyar';
		$hu->lang_code_iso_639_1 = 'hu';
		$hu->lang_code_iso_639_2 = 'hun';
		$hu->country_code = 'hu';
		$hu->slug = 'hu';

		$id = new GP_Locale();
		$id->english_name = 'Indonesian';
		$id->native_name = 'Bahasa Indonesia';
		$id->lang_code_iso_639_1 = 'id';
		$id->lang_code_iso_639_2 = 'ind';
		$id->country_code = 'id';
		$id->slug = 'id';

		$is = new GP_Locale();
		$is->english_name = 'Icelandic';
		$is->native_name = 'Íslenska';
		$is->lang_code_iso_639_1 = 'is';
		$is->lang_code_iso_639_2 = 'isl';
		$is->country_code = 'is';
		$is->slug = 'is';

		$it = new GP_Locale();
		$it->english_name = 'Italian';
		$it->native_name = 'Italiano';
		$it->lang_code_iso_639_1 = 'it';
		$it->lang_code_iso_639_2 = 'ita';
		$it->country_code = 'it';
		$it->slug = 'it';

		$kk = new GP_Locale();
		$kk->english_name = 'Kazakh';
		$kk->native_name = 'Қазақ тілі';
		$kk->lang_code_iso_639_1 = 'kk';
		$kk->lang_code_iso_639_2 = 'kaz';
		$kk->country_code = 'kz';
		$kk->slug = 'kk';

		$km = new GP_Locale();
		$km->english_name = 'Khmer';
		$km->native_name = 'ភាសាខ្មែរ';
		$km->lang_code_iso_639_1 = 'km';
		$km->lang_code_iso_639_2 = 'khm';
		$km->country_code = 'kh';
		$km->slug = 'km';

		$ku = new GP_Locale();
		$ku->english_name = 'Kurdish';
		$ku->native_name = 'Kurdî';
		$ku->lang_code_iso_639_1 = 'ku';
		$ku->lang_code_iso_639_2 = 'kur';
		$ku->country_code = '';
		$ku->slug = 'km';

		$lt = new GP_Locale();
		$lt->english_name = 'Lithuanian';
		$lt->native_name = 'Lietuvių kalba';
		$lt->lang_code_iso_639_1 = 'lt';
		$lt->lang_code_iso_639_2 = 'lit';
		$lt->country_code = 'lt';
		$lt->slug = 'lt';

		$lv = new GP_Locale();
		$lv->english_name = 'Latvian';
		$lv->native_name = 'Latviešu valoda';
		$lv->lang_code_iso_639_1 = 'lv';
		$lv->lang_code_iso_639_2 = 'lav';
		$lv->country_code = 'lv';
		$lv->slug = 'lv';

		$ml = new GP_Locale();
		$ml->english_name = 'Malayalam';
		$ml->native_name = 'മലയാളം';
		$ml->lang_code_iso_639_1 = 'ml';
		$ml->lang_code_iso_639_2 = 'mal';
		$ml->country_code = '';
		$ml->slug = 'ml';

		$mn = new GP_Locale();
		$mn->english_name = 'Mongolian';
		$mn->native_name = 'Монгол';
		$mn->lang_code_iso_639_1 = 'mn';
		$mn->lang_code_iso_639_2 = 'mon';
		$mn->country_code = 'mn';
		$mn->slug = 'mn';

		$mr = new GP_Locale();
		$mr->english_name = 'Marathi';
		$mr->native_name = 'मराठी';
		$mr->lang_code_iso_639_1 = 'mr';
		$mr->lang_code_iso_639_2 = 'mar';
		$mr->country_code = '';
		$mr->slug = 'mr';

		$ms = new GP_Locale();
		$ms->english_name = 'Malay';
		$ms->native_name = 'Bahasa Melayu';
		$ms->lang_code_iso_639_1 = 'ms';
		$ms->lang_code_iso_639_2 = 'msa';
		$ms->country_code = '';
		$ms->slug = 'ms';

		$mwl = new GP_Locale();
		$mwl->english_name = 'Mirandese';
		$mwl->native_name = 'Mirandés';
		$mwl->lang_code_iso_639_1 = null;
		$mwl->lang_code_iso_639_2 = 'mwl';
		$mwl->country_code = '';
		$mwl->slug = 'mwl';

		$nl = new GP_Locale();
		$nl->english_name = 'Dutch';
		$nl->native_name = 'Nederlands';
		$nl->lang_code_iso_639_1 = 'nl';
		$nl->lang_code_iso_639_2 = 'nld';
		$nl->country_code = 'nl';
		$nl->slug = 'nl';

		$no = new GP_Locale();
		$no->english_name = 'Norwegian';
		$no->native_name = 'Norsk';
		$no->lang_code_iso_639_1 = 'no';
		$no->lang_code_iso_639_2 = 'nor';
		$no->country_code = 'no';
		$no->slug = 'no';

		$nn = new GP_Locale();
		$nn->english_name = 'Norwegian Nynorsk';
		$nn->native_name = 'Norsk nynorsk';
		$nn->lang_code_iso_639_1 = 'nn';
		$nn->lang_code_iso_639_2 = 'nno';
		$nn->country_code = 'no';
		$nn->slug = 'nn';

		$oc = new GP_Locale();
		$oc->english_name = 'Occitan';
		$oc->native_name = 'Occitan';
		$oc->lang_code_iso_639_1 = 'oc';
		$oc->lang_code_iso_639_2 = 'oci';
		$oc->country_code = '';
		$oc->slug = 'oc';

		$pl = new GP_Locale();
		$pl->english_name = 'Polish';
		$pl->native_name = 'Polski';
		$pl->lang_code_iso_639_1 = 'pl';
		$pl->lang_code_iso_639_2 = 'pol';
		$pl->country_code = 'pl';
		$pl->slug = 'pl';

		$pt_br = new GP_Locale();
		$pt_br->english_name = 'Brazilian Portuguese';
		$pt_br->native_name = 'Português do Brasil';
		$pt_br->lang_code_iso_639_1 = 'pt';
		$pt_br->lang_code_iso_639_2 = 'por';
		$pt_br->country_code = 'br';
		$pt_br->slug = 'pt-br';

		$ro = new GP_Locale();
		$ro->english_name = 'Romanian';
		$ro->native_name = 'Română';
		$ro->lang_code_iso_639_1 = 'ro';
		$ro->lang_code_iso_639_2 = 'ron';
		$ro->country_code = 'ro';
		$ro->slug = 'ro';

		$ru = new GP_Locale();
		$ru->english_name = 'Russian';
		$ru->native_name = 'Русский';
		$ru->lang_code_iso_639_1 = 'ru';
		$ru->lang_code_iso_639_2 = 'rus';
		$ru->country_code = 'ru';
		$ru->slug = 'ru';

		$si = new GP_Locale();
		$si->english_name = 'Sinhala';
		$si->native_name = 'සිංහල';
		$si->lang_code_iso_639_1 = 'si';
		$si->lang_code_iso_639_2 = 'sin';
		$si->country_code = 'lk';
		$si->slug = 'si';

		$sk = new GP_Locale();
		$sk->english_name = 'Slovak';
		$sk->native_name = 'Slovenčina';
		$sk->lang_code_iso_639_1 = 'sk';
		$sk->lang_code_iso_639_2 = 'slk';
		$sk->country_code = 'sk';
		$sk->slug = 'sk';

		$sl = new GP_Locale();
		$sl->english_name = 'Slovenian';
		$sl->native_name = 'slovenščina';
		$sl->lang_code_iso_639_1 = 'sl';
		$sl->lang_code_iso_639_2 = 'slv';
		$sl->country_code = 'si';
		$sl->slug = 'sl';

		$sq = new GP_Locale();
		$sq->english_name = 'Albanian';
		$sq->native_name = 'Shqip';
		$sq->lang_code_iso_639_1 = 'sq';
		$sq->lang_code_iso_639_2 = 'sqi';
		$sq->country_code = 'al';
		$sq->slug = 'sq';

		$sr = new GP_Locale();
		$sr->english_name = 'Serbian';
		$sr->native_name = 'Српски језик';
		$sr->lang_code_iso_639_1 = 'sr';
		$sr->lang_code_iso_639_2 = 'srp';
		$sr->country_code = 'rs';
		$sr->slug = 'sr';

		$su = new GP_Locale();
		$su->english_name = 'Sundanese';
		$su->native_name = 'Basa Sunda';
		$su->lang_code_iso_639_1 = 'su';
		$su->lang_code_iso_639_2 = 'sun';
		$su->country_code = 'id';
		$su->slug = 'su';

		$sv = new GP_Locale();
		$sv->english_name = 'Swedish';
		$sv->native_name = 'Svenska';
		$sv->lang_code_iso_639_1 = 'sv';
		$sv->lang_code_iso_639_2 = 'swe';
		$sv->country_code = 'se';
		$sv->slug = 'sv';

		$ta = new GP_Locale();
		$ta->english_name = 'Tamil';
		$ta->native_name = 'தமிழ்';
		$ta->lang_code_iso_639_1 = 'ta';
		$ta->lang_code_iso_639_2 = 'tam';
		$ta->country_code = '';
		$ta->slug = 'ta';

		$te = new GP_Locale();
		$te->english_name = 'Telugu';
		$te->native_name = 'తెలుగు';
		$te->lang_code_iso_639_1 = 'te';
		$te->lang_code_iso_639_2 = 'tel';
		$te->country_code = '';
		$te->slug = 'te';

		$th = new GP_Locale();
		$th->english_name = 'Thai';
		$th->native_name = 'ไทย';
		$th->lang_code_iso_639_1 = 'th';
		$th->lang_code_iso_639_2 = 'tha';
		$th->country_code = '';
		$th->slug = 'th';
		
		$tl = new GP_Locale();
		$tl->english_name = 'Tagalog';
		$tl->native_name = 'Tagalog';
		$tl->lang_code_iso_639_1 = 'tl';
		$tl->lang_code_iso_639_2 = 'tgl';
		$tl->country_code = 'ph';
		$tl->slug = 'tl';

		$tr = new GP_Locale();
		$tr->english_name = 'Turkish';
		$tr->native_name = 'Türkçe';
		$tr->lang_code_iso_639_1 = 'tr';
		$tr->lang_code_iso_639_2 = 'tur';
		$tr->country_code = 'tr';
		$tr->slug = 'tr';

		$uk = new GP_Locale();
		$uk->english_name = 'Ukrainian';
		$uk->native_name = 'Українська';
		$uk->lang_code_iso_639_1 = 'uk';
		$uk->lang_code_iso_639_2 = 'ukr';
		$uk->country_code = 'ua';
		$uk->slug = 'uk';

		$vi = new GP_Locale();
		$vi->english_name = 'Vietnamese';
		$vi->native_name = 'Tiếng Việt';
		$vi->lang_code_iso_639_1 = 'vi';
		$vi->lang_code_iso_639_2 = 'vie';
		$vi->country_code = 'vn';
		$vi->slug = 'vi';

		$zh = new GP_Locale();
		$zh->english_name = 'Chinese';
		$zh->native_name = '中文';
		$zh->lang_code_iso_639_1 = 'zh';
		$zh->lang_code_iso_639_2 = 'zho';
		$zh->country_code = '';
		$zh->slug = 'zh';

		$zh_cn = new GP_Locale();
		$zh_cn->english_name = 'Chinese (China)';
		$zh_cn->native_name = '中文';
		$zh_cn->lang_code_iso_639_1 = 'zh';
		$zh_cn->lang_code_iso_639_2 = 'zho';
		$zh_cn->country_code = 'cn';
		$zh_cn->slug = 'zh-cn';

		$zh_hk = new GP_Locale();
		$zh_hk->english_name = 'Chinese (Honk Kong)';
		$zh_hk->native_name = '中文';
		$zh_hk->lang_code_iso_639_1 = 'zh';
		$zh_hk->lang_code_iso_639_2 = 'zho';
		$zh_hk->country_code = 'hk';
		$zh_hk->slug = 'zh-hk';

		$zh_sg = new GP_Locale();
		$zh_sg->english_name = 'Chinese (Singapore)';
		$zh_sg->native_name = '中文';
		$zh_sg->lang_code_iso_639_1 = 'zh';
		$zh_sg->lang_code_iso_639_2 = 'zho';
		$zh_sg->country_code = 'sg';
		$zh_sg->slug = 'zh-sg';

		$zh_tw = new GP_Locale();
		$zh_tw->english_name = 'Chinese (Taiwan)';
		$zh_tw->native_name = '中文';
		$zh_tw->lang_code_iso_639_1 = 'zh';
		$zh_tw->lang_code_iso_639_2 = 'zho';
		$zh_tw->country_code = 'tw';
		$zh_tw->slug = 'zh-tw';


		foreach( get_defined_vars() as $value ) {
			if ( isset( $value->english_name ) ) {
				if ( !isset( $value->direction ) ) {
					$value->direction = 'ltr';
				}
				$value->rtl = $value->direction == 'rtl';
				if ( !isset( $value->lang_code_iso_639_1 ) ) {
					$value->lang_code_iso_639_1 = null;
				}
				$this->locales[$value->slug] = $value;
			}
		}
	}
	
	function &instance() {
		if ( !isset( $GLOBALS['gp_locales'] ) )
			$GLOBALS['gp_locales'] = &new GP_Locales();
		return $GLOBALS['gp_locales'];
	}
	
	function locales() {
		$instance = &GP_Locales::instance();
		return $instance->locales;
	}
	
	function exists( $slug ) {
		$instance = &GP_Locales::instance();
		return isset( $instance->locales[$slug] );
	}
	
	function by_slug( $slug ) {
		$instance = &GP_Locales::instance();
		return isset( $instance->locales[$slug] )? $instance->locales[$slug] : null;
	}
}
?>
