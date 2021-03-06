<?php
/**
 * ISO_LANGUAGE can be defined as either 1 or 2, corresponding to ISO 639-1
 * (2 letter codes) and ISO 639-2 (3 letter codes), respectively.
 */
if (!defined('ISO_LANGUAGE')) {
    define('ISO_LANGUAGE', 1);
}

/**
 * ISO 639 Language Codes
 */
class ISO_Language
{
    /**
     * Returns an array of all language codes.
     *
     * @return array of all language codes
     */
    public static function codes() {
        return array_keys(self::$codes[ISO_LANGUAGE]);  
    }
    
    /**
     * Returns an array of all language names.
     * $pref, if non-null is an array of "preferred" language codes. If specified,
     * these codes will be moved to the start of the returned list. If a preferred
     * language list is used, $separator can be used to place a separator entry
     * between the preferred list and the rest. The key value for the separator
     * will always be the empty string.
     *
     * @param $pref array of 'preferred' language codes to be inserted at the top of the list
     * @param $sep separator string to place between perferred languages and remainder
     * @return array of language code => language name
     */
    public static function names($pref = null, $sep = null) {
        if ($pref !== null) {
            $out = array();
            foreach ($pref as $p) $out[$p] = self::$codes[ISO_LANGUAGE][$p];
            if ($sep !== null) $out[''] = $sep;
            foreach (self::$codes[ISO_LANGUAGE] as $c => $co) $out[$c] = $co;
            return $out;
        } else {
            return self::$codes[ISO_LANGUAGE];
        }
    }

    /**
     * Returns true if the specified language code exists.
     *
     * @param $code language code to query for
     * @return true if language code $code exists
     */
    public static function exists($code) {
        return isset(self::$codes[ISO_LANGUAGE][$code]);
    }
    
    /**
     * Look up a language by code.
     * If $default is supplied, and the code does not exist, $default will
     * be returned. Otherwise, if $code does not exist, an exception will be
     * thrown.
     *
     * @param $code language code to look up
     * @param $default default value to return if code does not exist
     * @return language name if $code exists, $default otherwise
     * @throws Error_NoSuchElement if code does not exist and no default is specified
     */
    public static function get_name($code, $default = null) {
        if (!self::exists($code)) {
            if ($default === null) {
                throw new Error_NoSuchElement("Language code '$code' does not exist");
            } else {
                return $default;
            }
        }
        return self::$codes[ISO_LANGUAGE][$code];
    }
    
    private static $codes = array(
        1 => array(
            'aa' => 'Afar',
            'ab' => 'Abkhazian',
            'af' => 'Afrikaans',
            'am' => 'Amharic',
            'ar' => 'Arabic',
            'as' => 'Assamese',
            'ay' => 'Aymara',
            'az' => 'Azerbaijani',
            'ba' => 'Bashkir',
            'be' => 'Byelorussian',
            'bg' => 'Bulgarian',
            'bh' => 'Bihari',
            'bi' => 'Bislama',
            'bn' => 'Bengali; Bangla',
            'bo' => 'Tibetan',
            'br' => 'Breton',
            'ca' => 'Catalan',
            'co' => 'Corsican',
            'cs' => 'Czech',
            'cy' => 'Welsh',
            'da' => 'Danish',
            'de' => 'German',
            'dz' => 'Bhutani',
            'el' => 'Greek',
            'en' => 'English',
            'eo' => 'Esperanto',
            'es' => 'Spanish',
            'et' => 'Estonian',
            'eu' => 'Basque',
            'fa' => 'Persian',
            'fi' => 'Finnish',
            'fj' => 'Fiji',
            'fo' => 'Faeroese',
            'fr' => 'French',
            'fy' => 'Frisian',
            'ga' => 'Irish',
            'gd' => 'Scots Gaelic',
            'gl' => 'Galician',
            'gn' => 'Guarani',
            'gu' => 'Gujarati',
            'ha' => 'Hausa',
            'hi' => 'Hindi',
            'hr' => 'Croatian',
            'hu' => 'Hungarian',
            'hy' => 'Armenian',
            'ia' => 'Interlingua',
            'ie' => 'Interlingue',
            'ik' => 'Inupiak',
            'in' => 'Indonesian',
            'is' => 'Icelandic',
            'it' => 'Italian',
            'iw' => 'Hebrew',
            'ja' => 'Japanese',
            'ji' => 'Yiddish',
            'jw' => 'Javanese',
            'ka' => 'Georgian',
            'kk' => 'Kazakh',
            'kl' => 'Greenlandic',
            'km' => 'Cambodian',
            'kn' => 'Kannada',
            'ko' => 'Korean',
            'ks' => 'Kashmiri',
            'ku' => 'Kurdish',
            'ky' => 'Kirghiz',
            'la' => 'Latin',
            'ln' => 'Lingala',
            'lo' => 'Laothian',
            'lt' => 'Lithuanian',
            'lv' => 'Latvian, Lettish',
            'mg' => 'Malagasy',
            'mi' => 'Maori',
            'mk' => 'Macedonian',
            'ml' => 'Malayalam',
            'mn' => 'Mongolian',
            'mo' => 'Moldavian',
            'mr' => 'Marathi',
            'ms' => 'Malay',
            'mt' => 'Maltese',
            'my' => 'Burmese',
            'na' => 'Nauru',
            'ne' => 'Nepali',
            'nl' => 'Dutch',
            'no' => 'Norwegian',
            'oc' => 'Occitan',
            'om' => '(Afan) Oromo',
            'or' => 'Oriya',
            'pa' => 'Punjabi',
            'pl' => 'Polish',
            'ps' => 'Pashto, Pushto',
            'pt' => 'Portuguese',
            'qu' => 'Quechua',
            'rm' => 'Rhaeto-Romance',
            'rn' => 'Kirundi',
            'ro' => 'Romanian',
            'ru' => 'Russian',
            'rw' => 'Kinyarwanda',
            'sa' => 'Sanskrit',
            'sd' => 'Sindhi',
            'sg' => 'Sangro',
            'sh' => 'Serbo-Croatian',
            'si' => 'Singhalese',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'sm' => 'Samoan',
            'sn' => 'Shona',
            'so' => 'Somali',
            'sq' => 'Albanian',
            'sr' => 'Serbian',
            'ss' => 'Siswati',
            'st' => 'Sesotho',
            'su' => 'Sundanese',
            'sv' => 'Swedish',
            'sw' => 'Swahili',
            'ta' => 'Tamil',
            'te' => 'Tegulu',
            'tg' => 'Tajik',
            'th' => 'Thai',
            'ti' => 'Tigrinya',
            'tk' => 'Turkmen',
            'tl' => 'Tagalog',
            'tn' => 'Setswana',
            'to' => 'Tonga',
            'tr' => 'Turkish',
            'ts' => 'Tsonga',
            'tt' => 'Tatar',
            'tw' => 'Twi',
            'uk' => 'Ukrainian',
            'ur' => 'Urdu',
            'uz' => 'Uzbek',
            'vi' => 'Vietnamese',
            'vo' => 'Volapuk',
            'wo' => 'Wolof',
            'xh' => 'Xhosa',
            'yo' => 'Yoruba',
            'zh' => 'Chinese',
            'zu' => 'Zulu'
        ),
        2 => array(
            'aar' => 'Afar',
            'abk' => 'Abkhazian',
            'ace' => 'Achinese',
            'ach' => 'Acoli',
            'ada' => 'Adangme',
            'afa' => 'Afro-Asiatic (Other)',
            'afh' => 'Afrihili',
            'afr' => 'Africaans',
            'ajm' => 'Aljamia',
            'aka' => 'Akan',
            'akk' => 'Akkadian',
            'alb' => 'Albanian',
            'sqi' => 'Albanian',
            'ale' => 'Aleut',
            'alg' => 'Algonquian languages',
            'amh' => 'Amharic',
            'ang' => 'English, Old (ca. 450-1100)',
            'apa' => 'Apache languages',
            'ara' => 'Arabic',
            'arc' => 'Aramaic',
            'arm' => 'Armenian',
            'hye' => 'Armenian',
            'arn' => 'Araucanian',
            'arp' => 'Arapaho',
            'art' => 'Artificial (Other)',
            'arw' => 'Arawak',
            'asm' => 'Assamese',
            'ath' => 'Athapascan languages',
            'ava' => 'Avaric',
            'ave' => 'Avestan',
            'awa' => 'Awandhi',
            'aym' => 'Aymara',
            'aze' => 'Azerbaijani',
            'bad' => 'Banda',
            'bai' => 'Bamileke languages',
            'bak' => 'Bashkir',
            'bal' => 'Baluchi',
            'bam' => 'Bambara',
            'ban' => 'Balinese',
            'baq' => 'Basque',
            'eus' => 'Basque',
            'bas' => 'Basa',
            'bat' => 'Baltic (Other)',
            'bej' => 'Beja',
            'bel' => 'Byelorussian',
            'bem' => 'Bemba',
            'ben' => 'Bengali',
            'ber' => 'Berber languages',
            'bho' => 'Bhojpuri',
            'bih' => 'Bihari',
            'bik' => 'Bikol',
            'bin' => 'Bini',
            'bis' => 'Bislama',
            'bla' => 'Siksika',
            'bod' => 'Tibetan',
            'tib' => 'Tibetan',
            'bra' => 'Braj',
            'bre' => 'Breton',
            'bug' => 'Buginese',
            'bul' => 'Bulgarian',
            'bur' => 'Burmese',
            'mya' => 'Burmese',
            'cad' => 'Caddo',
            'cai' => 'Central American Indian (Other)',
            'car' => 'Carib',
            'cat' => 'Catalan',
            'cau' => 'Caucasian (Other)',
            'ceb' => 'Cebuano',
            'cel' => 'Celtic (Other)',
            'ces' => 'Czeck',
            'cze' => 'Czeck',
            'cha' => 'Chamorro',
            'chb' => 'Chibcha',
            'che' => 'Chechen',
            'chg' => 'Chagatai',
            'chi' => 'Chinese',
            'zho' => 'Chinese',
            'chn' => 'Chinook jargon',
            'cho' => 'Choctaw',
            'chr' => 'Cherokee',
            'chu' => 'Church Slavic',
            'chv' => 'Chuvash',
            'chy' => 'Cheyenne',
            'cop' => 'Coptic',
            'cor' => 'Cornish',
            'cos' => 'Corsican',
            'cpe' => 'Creoles and pidgins, English-based (Other)',
            'cpf' => 'Creoles and pidgins, French-based (Other)',
            'cpp' => 'Creoles and pidgins, Portuguese-based (Other)',
            'cre' => 'Cree',
            'crp' => 'Creoles and pidgins (Other)',
            'cus' => 'Cushitic (Other)',
            'cym' => 'Welsh',
            'wel' => 'Welsh',
            'cze' => 'Czech',
            'ces' => 'Czech',
            'dak' => 'Dakota',
            'dan' => 'Danish',
            'del' => 'Delaware',
            'deu' => 'German',
            'ger' => 'German',
            'din' => 'Dinka',
            'doi' => 'Dogri',
            'dra' => 'Dravidian (Other)',
            'dua' => 'Duala',
            'dum' => 'Dutch, Middle (ca. 1050-1350)',
            'dut' => 'Dutch',
            'nld' => 'Dutch',
            'dyu' => 'Dyula',
            'dzo' => 'Dzongkha',
            'efi' => 'Efik',
            'egy' => 'Egyptian (Ancient)',
            'eka' => 'Ekajuk',
            'ell' => 'Greek, Modern (1453- )',
            'gre' => 'Greek, Modern (1453- )',
            'elx' => 'Elamite',
            'eng' => 'English',
            'enm' => 'English, Middle (1100-1500)',
            'epo' => 'Esperanto',
            'esk' => 'Eskimo (Other)',
            'esl' => 'Spanish',
            'spa' => 'Spanish',
            'est' => 'Estonian',
            'eth' => 'Ethiopic',
            'eus' => 'Basque',
            'baq' => 'Basque',
            'ewe' => 'Ewe',
            'ewo' => 'Ewondo',
            'fan' => 'Fang',
            'fao' => 'Faroese',
            'fas' => 'Persian',
            'per' => 'Persian',
            'fat' => 'Fanti',
            'fij' => 'Fijian',
            'fin' => 'Finnish',
            'fiu' => 'Finno-Ugrian (Other)',
            'fon' => 'Fon',
            'fra' => 'French',
            'fre' => 'French',
            'fre' => 'French',
            'fra' => 'French',
            'frm' => 'French, Middle (ca. 1400-1600)',
            'fro' => 'French, Old (ca. 842-1400)',
            'fry' => 'Friesian',
            'ful' => 'Fulah',
            'gaa' => 'Ga',
            'gae' => 'Gaelic (Scots)',
            'gdh' => 'Gaelic (Scots)',
            'gai' => 'Irish',
            'iri' => 'Irish',
            'gay' => 'Gayo',
            'gdh' => 'Gaelic (Scots)',
            'gae' => 'Gaelic (Scots)',
            'gem' => 'Germanic (Other)',
            'geo' => 'Georgian',
            'kat' => 'Georgian',
            'ger' => 'German',
            'deu' => 'German',
            'gil' => 'Gilbertese',
            'glg' => 'Gallegan',
            'gmh' => 'German, Mid. High (ca. 1050-1500)',
            'goh' => 'German, Old High (ca. 750-1050)',
            'gon' => 'Gondi',
            'got' => 'Gothic',
            'grb' => 'Grebo',
            'grc' => 'Greek, Ancient (to 1453)',
            'gre' => 'Greek, Modern (1453- )',
            'ell' => 'Greek, Modern (1453- )',
            'grn' => 'Guarani',
            'guj' => 'Gujarati',
            'hai' => 'Haida',
            'hau' => 'Hausa',
            'haw' => 'Hawaiian',
            'heb' => 'Hebrew',
            'her' => 'Herero',
            'hil' => 'Hiligaynon',
            'him' => 'Himachali',
            'hin' => 'Hindi',
            'hmo' => 'Hiri Motu',
            'hun' => 'Hungarian',
            'hup' => 'Hupa',
            'hye' => 'Armenian',
            'arm' => 'Armenian',
            'iba' => 'Iban',
            'ibo' => 'Igbo',
            'ice' => 'Icelandic',
            'isl' => 'Icelandic',
            'ijo' => 'Ijo',
            'iku' => 'Inuktitut',
            'ile' => 'Interlingue',
            'ilo' => 'Iloko',
            'ina' => 'Interlingua',
            'inc' => 'Indic (Other)',
            'ind' => 'Indonesian',
            'ine' => 'Indo-European (Other)',
            'ipk' => 'Inupiak',
            'ira' => 'Iranian (Other)',
            'iri' => 'Irish',
            'gai' => 'Irish',
            'iro' => 'Iroquoian languages',
            'isl' => 'Icelandic',
            'ice' => 'Icelandic',
            'ita' => 'Italian',
            'jav' => 'Javanese',
            'jaw' => 'Javanese',
            'jaw' => 'Javanese',
            'jav' => 'Javanese',
            'jpn' => 'Japanese',
            'jpr' => 'Judeo-Persian',
            'jrb' => 'Judeo-Arabic',
            'kaa' => 'Kara-Kalpak',
            'kab' => 'Kabyle',
            'kac' => 'Kachin',
            'kal' => 'Greenlandic',
            'kam' => 'Kamba',
            'kan' => 'Kannada',
            'kar' => 'Karen',
            'kas' => 'Kashmiri',
            'kat' => 'Georgian',
            'geo' => 'Georgian',
            'kau' => 'Kanuri',
            'kaw' => 'Kawi',
            'kaz' => 'Kazakh',
            'kha' => 'Khasi',
            'khi' => 'Khoisan (Other)',
            'khm' => 'Khmer',
            'kho' => 'Khotanese',
            'kik' => 'Kikuyu',
            'kin' => 'Kinyarwanda',
            'kir' => 'Kirghiz',
            'kok' => 'Konkani',
            'kon' => 'Kongo',
            'kor' => 'Korean',
            'kpe' => 'Kpelle',
            'kro' => 'Kru',
            'kru' => 'Kurukh',
            'kua' => 'Kuanyama',
            'kur' => 'Kurdish',
            'kus' => 'Kusaie',
            'kut' => 'Kutenai',
            'lad' => 'Ladino',
            'lah' => 'Lahnda',
            'lam' => 'Lamba',
            'lao' => 'Lao',
            'lap' => 'Lapp languages',
            'lat' => 'Latin',
            'lav' => 'Latvian',
            'lin' => 'Lingala',
            'lit' => 'Lithuanian',
            'lol' => 'Mongo',
            'loz' => 'Lozi',
            'lub' => 'Luba-Katanga',
            'lug' => 'Ganda',
            'lui' => 'Luiseno',
            'lun' => 'Lunda',
            'luo' => 'Luo (Kenya and Tanzania)',
            'mac' => 'Macedonian',
            'mke' => 'Macedonian',
            'mad' => 'Madurese',
            'mag' => 'Magahi',
            'mah' => 'Marshall',
            'mai' => 'Maithili',
            'mak' => 'Makasar',
            'mal' => 'Malayalam',
            'man' => 'Mandingo',
            'mao' => 'Maori',
            'mri' => 'Maori',
            'map' => 'Austronesian (Other)',
            'mar' => 'Marathi',
            'mas' => 'Masai',
            'max' => 'Manx',
            'may' => 'Malay',
            'msa' => 'Malay',
            'men' => 'Mende',
            'mic' => 'Micmac',
            'min' => 'Minangkabau',
            'mis' => 'Miscellaneous (Other)',
            'mke' => 'Macedonian',
            'mac' => 'Macedonian',
            'mkh' => 'Mon-Khmer (Other)',
            'mlg' => 'Malagasy',
            'mlt' => 'Maltese',
            'mni' => 'Manipuri',
            'mno' => 'Manobo languages',
            'moh' => 'Mohawk',
            'mol' => 'Moldavian',
            'mon' => 'Mongolian',
            'mos' => 'Mossi',
            'mri' => 'Maori',
            'mao' => 'Maori',
            'msa' => 'Malay',
            'may' => 'Malay',
            'mul' => 'Multiple languages',
            'mun' => 'Munda (Other)',
            'mus' => 'Creek',
            'mwr' => 'Marwari',
            'mya' => 'Burmese',
            'bur' => 'Burmese',
            'myn' => 'Mayan languages',
            'nah' => 'Aztec',
            'nai' => 'North American Indian (Other)',
            'nau' => 'Nauru',
            'nav' => 'Navajo',
            'nde' => 'Ndebele (Zimbabwe)',
            'nld' => 'Dutch',
            'dut' => 'Dutch',
            'ndo' => 'Ndonga',
            'nep' => 'Nepali',
            'new' => 'Newari',
            'nic' => 'Niger-Kordofanian (Other)',
            'niu' => 'Niuean',
            'non' => 'Old Norse',
            'nor' => 'Norwegian',
            'nso' => 'Northern Sohto',
            'nub' => 'Nubian languages',
            'nya' => 'Nyanja',
            'nym' => 'Nyamwezi',
            'nyn' => 'Nyankole',
            'nyo' => 'Nyoro',
            'nzi' => 'Nzima',
            'oci' => 'Langue d\'oc (post 1500)',
            'oji' => 'Ojibwa',
            'ori' => 'Oriya',
            'orm' => 'Oromo',
            'osa' => 'Osage',
            'oss' => 'Ossetic',
            'ota' => 'Turkish, Ottoman',
            'oto' => 'Otomian languages',
            'paa' => 'Papuan-Australian (Other)',
            'pag' => 'Pangasinan',
            'pal' => 'Pahlavi',
            'pam' => 'Pampanga',
            'pan' => 'Panjabi',
            'pap' => 'Papiamento',
            'pau' => 'Palauan',
            'peo' => 'Old Persian (ca. 600-400 B.C.)',
            'per' => 'Persian',
            'fas' => 'Persian',
            'pli' => 'Pali',
            'pol' => 'Polish',
            'pon' => 'Ponape',
            'por' => 'Portuguese',
            'pra' => 'Prakrit languages',
            'pro' => 'Provencal, Old (to 1500)',
            'pus' => 'Pushto',
            'que' => 'Quechua',
            'raj' => 'Rajasthani',
            'rar' => 'Rarotongan',
            'roa' => 'Romance (Other)',
            'roh' => 'Raeto-Romance',
            'rom' => 'Romany',
            'ron' => 'Romanian',
            'rum' => 'Romanian',
            'rum' => 'Romanian',
            'ron' => 'Romanian',
            'run' => 'Rundi',
            'rus' => 'Russian',
            'sad' => 'Sandawe',
            'sag' => 'Sango',
            'sai' => 'South American Indian (Other)',
            'sal' => 'Salishan languages',
            'sam' => 'Samaritan Aramaic',
            'san' => 'Sanskrit',
            'sco' => 'Scots',
            'scr' => 'Serbo-Croatian',
            'sel' => 'Selkup',
            'sem' => 'Semitic (Other)',
            'shn' => 'Shan',
            'sid' => 'Sidamo',
            'sin' => 'Sinhalese',
            'sio' => 'Siouan languages',
            'sit' => 'Sino-Tibetan (Other)',
            'sla' => 'Slavic (Other)',
            'slk' => 'Slovak',
            'slo' => 'Slovak',
            'slo' => 'Slovak',
            'slk' => 'Slovak',
            'slv' => 'Slovenian',
            'smo' => 'Samoan',
            'sna' => 'Shona',
            'snd' => 'Sindhi',
            'sog' => 'Sogdian',
            'som' => 'Somali',
            'son' => 'Songhai',
            'sot' => 'Sotho',
            'spa' => 'Spanish',
            'esl' => 'Spanish',
            'sqi' => 'Albanian',
            'alb' => 'Albanian',
            'srr' => 'Serer',
            'ssa' => 'Nilo-Saharan (Other)',
            'ssw' => 'Swazi',
            'suk' => 'Sukuma',
            'sun' => 'Sundanese',
            'sus' => 'Susu',
            'sux' => 'Sumerian',
            'sve' => 'Swedish',
            'swe' => 'Swedish',
            'swa' => 'Swahili',
            'swe' => 'Swedish',
            'sve' => 'Swedish',
            'syr' => 'Syriac',
            'tah' => 'Tahitian',
            'tam' => 'Tamil',
            'tat' => 'Tatar',
            'tel' => 'Telugu',
            'tem' => 'Timne',
            'ter' => 'Tereno',
            'tgk' => 'Tajik',
            'tgl' => 'Tagalog',
            'tha' => 'Thai',
            'tib' => 'Tibetan',
            'bod' => 'Tibetan',
            'tig' => 'Tigre',
            'tir' => 'Tigrinya',
            'tiv' => 'Tivi',
            'tli' => 'Tlingit',
            'tog' => 'Tonga (Nyasa)',
            'ton' => 'Tonga (Tonga Islands)',
            'tru' => 'Truk',
            'tsi' => 'Tsimshian',
            'tsn' => 'Tswana',
            'tso' => 'Tsonga',
            'tuk' => 'Turkmen',
            'tum' => 'Tumbuka',
            'tur' => 'Turkish',
            'tut' => 'Altaic (Other)',
            'twi' => 'Twi',
            'uga' => 'Ugaritic',
            'uig' => 'Uighur',
            'ukr' => 'Ukrainian',
            'umb' => 'Umbundu',
            'und' => 'Undetermined',
            'urd' => 'Urdu',
            'uzb' => 'Uzbek',
            'vai' => 'Vai',
            'ven' => 'Venda',
            'vie' => 'Vietnamese',
            'vol' => 'Volapuk',
            'vot' => 'Votic',
            'wak' => 'Wakashan languages',
            'wal' => 'Walamo',
            'war' => 'Waray',
            'was' => 'Washo',
            'wel' => 'Welsh',
            'cym' => 'Welsh',
            'wen' => 'Sorbian languages',
            'wol' => 'Wolof',
            'xho' => 'Xhosa',
            'yao' => 'Yao',
            'yap' => 'Yap',
            'yid' => 'Yiddish',
            'yor' => 'Yoruba',
            'zap' => 'Zapotec',
            'zen' => 'Zenaga',
            'zha' => 'Zhuang',
            'zho' => 'Chinese',
            'chi' => 'Chinese',
            'zul' => 'Zulu',
            'zun' => 'Zuni',
        )
    );
}
?>