<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Service\Translation;

use Pagemachine\AItools\Service\SettingsService;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CustomTranslationService
{
    protected $requestFactory;
    protected string $authToken = '';
    protected string $basicAuth = '';
    protected $settingsService;

    private array $languages = [
        'ace' => 'ace_Arab',  // ace_Latn
        'acm' => 'acm_Arab',
        'acq' => 'acq_Arab',
        'aeb' => 'aeb_Arab',
        'af' => 'afr_Latn',
        'ajp' => 'ajp_Arab',
        'ak' => 'aka_Latn',
        'am' => 'amh_Ethi',
        'apc' => 'apc_Arab',
        'arb' => 'arb_Arab',
        'ars' => 'ars_Arab',
        'ary' => 'ary_Arab',
        'arz' => 'arz_Arab',
        'as' => 'asm_Beng',
        'ast' => 'ast_Latn',
        'awa' => 'awa_Deva',
        'ayr' => 'ayr_Latn',
        'azb' => 'azb_Arab',
        'azj' => 'azj_Latn',
        'ba' => 'bak_Cyrl',
        'bm' => 'bam_Latn',
        'ban' => 'ban_Latn',
        'be' => 'bel_Cyrl',
        'bem' => 'bem_Latn',
        'bn' => 'ben_Beng',
        'bho' => 'bho_Deva',
        'bjn' => 'bjn_Arab',  // bjn_Latn
        'bo' => 'bod_Tibt',
        'bs' => 'bos_Latn',
        'bug' => 'bug_Latn',
        'bg' => 'bul_Cyrl',
        'ca' => 'cat_Latn',
        'ceb' => 'ceb_Latn',
        'cs' => 'ces_Latn',
        'cjk' => 'cjk_Latn',
        'ckb' => 'ckb_Arab',
        'crh' => 'crh_Latn',
        'cy' => 'cym_Latn',
        'da' => 'dan_Latn',
        'de' => 'deu_Latn',
        'dik' => 'dik_Latn',
        'dyu' => 'dyu_Latn',
        'dz' => 'dzo_Tibt',
        'el' => 'ell_Grek',
        'en' => 'eng_Latn',
        'eo' => 'epo_Latn',
        'et' => 'est_Latn',
        'eu' => 'eus_Latn',
        'ee' => 'ewe_Latn',
        'fo' => 'fao_Latn',
        'pes' => 'pes_Arab',
        'fj' => 'fij_Latn',
        'fi' => 'fin_Latn',
        'fon' => 'fon_Latn',
        'fr' => 'fra_Latn',
        'fur' => 'fur_Latn',
        'fuv' => 'fuv_Latn',
        'gd' => 'gla_Latn',
        'ga' => 'gle_Latn',
        'gl' => 'glg_Latn',
        'gn' => 'grn_Latn',
        'gu' => 'guj_Gujr',
        'ht' => 'hat_Latn',
        'ha' => 'hau_Latn',
        'he' => 'heb_Hebr',
        'hi' => 'hin_Deva',
        'hne' => 'hne_Deva',
        'hr' => 'hrv_Latn',
        'hu' => 'hun_Latn',
        'hy' => 'hye_Armn',
        'ig' => 'ibo_Latn',
        'ilo' => 'ilo_Latn',
        'id' => 'ind_Latn',
        'is' => 'isl_Latn',
        'it' => 'ita_Latn',
        'jv' => 'jav_Latn',
        'ja' => 'jpn_Jpan',
        'kab' => 'kab_Latn',
        'kac' => 'kac_Latn',
        'kam' => 'kam_Latn',
        'kn' => 'kan_Knda',
        'ks' => 'kas_Arab',  // kas_Deva
        'ka' => 'kat_Geor',
        'knc' => 'knc_Arab',  // knc_Latn
        'kk' => 'kaz_Cyrl',
        'kbp' => 'kbp_Latn',
        'kea' => 'kea_Latn',
        'km' => 'khm_Khmr',
        'ki' => 'kik_Latn',
        'rw' => 'kin_Latn',
        'ky' => 'kir_Cyrl',
        'kmb' => 'kmb_Latn',
        'kg' => 'kon_Latn',
        'ko' => 'kor_Hang',
        'kmr' => 'kmr_Latn',
        'lo' => 'lao_Laoo',
        'lvs' => 'lvs_Latn',
        'lij' => 'lij_Latn',
        'li' => 'lim_Latn',
        'ln' => 'lin_Latn',
        'lt' => 'lit_Latn',
        'lmo' => 'lmo_Latn',
        'ltg' => 'ltg_Latn',
        'lb' => 'ltz_Latn',
        'lua' => 'lua_Latn',
        'lg' => 'lug_Latn',
        'luo' => 'luo_Latn',
        'lus' => 'lus_Latn',
        'mag' => 'mag_Deva',
        'mai' => 'mai_Deva',
        'ml' => 'mal_Mlym',
        'mr' => 'mar_Deva',
        'min' => 'min_Latn',
        'mk' => 'mkd_Cyrl',
        'plt' => 'plt_Latn',
        'mt' => 'mlt_Latn',
        'mni' => 'mni_Beng',
        'khk' => 'khk_Cyrl',
        'mos' => 'mos_Latn',
        'mi' => 'mri_Latn',
        'zsm' => 'zsm_Latn',
        'my' => 'mya_Mymr',
        'nl' => 'nld_Latn',
        'nn' => 'nno_Latn',
        'nb' => 'nob_Latn',
        'npi' => 'npi_Deva',
        'nso' => 'nso_Latn',
        'nus' => 'nus_Latn',
        'ny' => 'nya_Latn',
        'oc' => 'oci_Latn',
        'gaz' => 'gaz_Latn',
        'ory' => 'ory_Orya',
        'pag' => 'pag_Latn',
        'pa' => 'pan_Guru',
        'pap' => 'pap_Latn',
        'pl' => 'pol_Latn',
        'pt' => 'por_Latn',
        'prs' => 'prs_Arab',
        'pbt' => 'pbt_Arab',
        'quy' => 'quy_Latn',
        'ro' => 'ron_Latn',
        'rn' => 'run_Latn',
        'ru' => 'rus_Cyrl',
        'sg' => 'sag_Latn',
        'sa' => 'san_Deva',
        'sat' => 'sat_Beng',
        'scn' => 'scn_Latn',
        'shn' => 'shn_Mymr',
        'si' => 'sin_Sinh',
        'sk' => 'slk_Latn',
        'sl' => 'slv_Latn',
        'sm' => 'smo_Latn',
        'sn' => 'sna_Latn',
        'sd' => 'snd_Arab',
        'so' => 'som_Latn',
        'st' => 'sot_Latn',
        'es' => 'spa_Latn',
        'als' => 'als_Latn',
        'sc' => 'srd_Latn',
        'sr' => 'srp_Cyrl',
        'ss' => 'ssw_Latn',
        'su' => 'sun_Latn',
        'sv' => 'swe_Latn',
        'swh' => 'swh_Latn',
        'szl' => 'szl_Latn',
        'ta' => 'tam_Taml',
        'tt' => 'tat_Cyrl',
        'te' => 'tel_Telu',
        'tg' => 'tgk_Cyrl',
        'tl' => 'tgl_Latn',
        'th' => 'tha_Thai',
        'ti' => 'tir_Ethi',
        'taq' => 'taq_Tfng',  // taq_Latn
        'tpi' => 'tpi_Latn',
        'tn' => 'tsn_Latn',
        'ts' => 'tso_Latn',
        'tk' => 'tuk_Latn',
        'tum' => 'tum_Latn',
        'tr' => 'tur_Latn',
        'tw' => 'twi_Latn',
        'tzm' => 'tzm_Tfng',
        'ug' => 'uig_Arab',
        'uk' => 'ukr_Cyrl',
        'umb' => 'umb_Latn',
        'ur' => 'urd_Arab',
        'uzn' => 'uzn_Latn',
        'vec' => 'vec_Latn',
        'vi' => 'vie_Latn',
        'war' => 'war_Latn',
        'wo' => 'wol_Latn',
        'xh' => 'xho_Latn',
        'ydd' => 'ydd_Hebr',
        'yo' => 'yor_Latn',
        'yue' => 'yue_Hant',
        'zh' => 'zho_Hans',  // zho_Hant
        'zu' => 'zul_Latn',
    ];

    public function __construct()
    {
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $this->authToken = $this->settingsService->getSetting('custom_auth_token');
        // Retrieve the username and password from settings
        $username = $this->settingsService->getSetting('custom_api_username');
        $password = $this->settingsService->getSetting('custom_api_password');
        if (!empty($username) && !empty($password)) {
            $this->basicAuth = base64_encode($username . ':' . $password);
        }
    }

    private function getLanguageScript($code)
    {
        return $this->languages[$code] ?? null;
    }

    public function sendTranslationRequestToApi(string $text, string $sourceLang = 'en', string $targetLang = 'en'): string
    {
        $sourceLang = $this->getLanguageScript($sourceLang);
        $targetLang = $this->getLanguageScript($targetLang);

        $url = $this->settingsService->getSetting('custom_translation_api_uri');

        $url .= '?source_lang=' . urlencode($sourceLang) . '&target_lang=' . urlencode($targetLang);

        // Prepare the form data
        $formData = http_build_query([
            'text' => $text,
        ]);

        $response = $this->requestFactory->request($url, 'POST', [
            'headers' => [
                'X-Auth-Token' => $this->authToken,
                'Authorization' => 'Basic ' . $this->basicAuth,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => $formData,
        ]);

        if ($response->getStatusCode() === 200) {
            return $response->getBody()->getContents();
        }

        throw new \Exception('API request failed');
    }
}
