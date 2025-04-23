<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Channels source class
 */
class Channels implements OptionSourceInterface
{

    public const ALIEXPRESS = 'aliexpress';
    public const ALLEGRO = 'allegro';
    public const AMAZON = 'amazon';
    public const ARISE = 'arise';
    public const BESLIST = 'beslist';
    public const BOL = 'bol';
    public const CDISCOUNT = 'cdiscount';
    public const CHANNABLE_SANDBOX = 'channable_sandbox';
    public const CHECK24 = 'check24';
    public const EBAY = 'ebay';
    public const FNAC = 'fnac';
    public const FRUUGO = 'fruugo';
    public const GALAXUS = 'galaxus';
    public const HOMEDECO = 'homedeco';
    public const IDEALO = 'idealo';
    public const KAUFLAND = 'kaufland';
    public const MANOMANO = 'manomano';
    public const MIINTO = 'miinto';
    public const MIINTO_NEW = 'miinto_new';
    public const MIRAKL_ANWB = 'mirakl_anwb';
    public const MIRAKL_BUT = 'mirakl_but';
    public const MIRAKL_CARREFOUR = 'mirakl_carrefour';
    public const MIRAKL_CARREFOUR_FR = 'mirakl_carrefour_fr';
    public const MIRAKL_CONFORAMA = 'mirakl_conforama';
    public const MIRAKL_CONRAD = 'mirakl_conrad';
    public const MIRAKL_COOLSHOP = 'mirakl_coolshop';
    public const MIRAKL_DARTY = 'mirakl_darty';
    public const MIRAKL_DEBENHAMS = 'mirakl_debenhams';
    public const MIRAKL_DECATHLON = 'mirakl_decathlon';
    public const MIRAKL_DOUGLAS = 'mirakl_douglas';
    public const MIRAKL_EPRICE = 'mirakl_eprice';
    public const MIRAKL_FONQ = 'mirakl_fonq';
    public const MIRAKL_GALERIES_LAFAYETTE = 'mirakl_galeries_lafayette';
    public const MIRAKL_HOME24 = 'mirakl_home24';
    public const MIRAKL_INNO = 'mirakl_inno';
    public const MIRAKL_KRUIDVAT = 'mirakl_kruidvat';
    public const MIRAKL_LA_REDOUTE = 'mirakl_la_redoute';
    public const MIRAKL_LE_BHV_MARAIS = 'mirakl_le_bhv_marais';
    public const MIRAKL_LEEN_BAKKER = 'mirakl_leen_bakker';
    public const MIRAKL_LEROY_MERLIN = 'mirakl_leroy_merlin';
    public const MIRAKL_MAISONS_DU_MONDE = 'mirakl_maisons_du_monde';
    public const MIRAKL_MANOR = 'mirakl_manor';
    public const MIRAKL_MAXEDA_BRICO = 'mirakl_maxeda_brico';
    public const MIRAKL_MAXEDA_PRAXIS = 'mirakl_maxeda_praxis';
    public const MIRAKL_MEDIAMARKT = 'mirakl_mediamarkt';
    public const MIRAKL_NEXTAIL = 'mirakl_nextail';
    public const MIRAKL_NOCIBE = 'mirakl_nocibe';
    public const MIRAKL_OBELINK = 'mirakl_obelink';
    public const MIRAKL_PCCOMPONENTES = 'mirakl_pccomponentes';
    public const MIRAKL_VTWONEN = 'mirakl_vtwonen';
    public const MIRAKL_WORTEN = 'mirakl_worten';
    public const OTTO = 'otto';
    public const RAKUTEN = 'rakuten';
    public const REAL = 'real';
    public const SPARTOO = 'spartoo';
    public const TIKTOK = 'tiktok';
    public const TIKTOK_US = 'tiktok_us';
    public const TOBEDRESSED = 'tobedressed';
    public const V_AND_D = 'v_and_d';
    public const VEEPEE = 'veepee';
    public const WALMART = 'walmart';
    public const WAYFAIR = 'wayfair';
    public const WISH = 'wish';
    public const ZALANDO = 'zalando';


    private $options = null;

    /**
     * Retrieve the list of channels as options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $this->options = [];
            foreach ($this->getAllChannels() as $channel) {
                $this->options[] = [
                    'value' => $channel,
                    'label' => $this->getChannelName($channel),
                ];
            }

            // Sort options alphabetically by label
            usort($this->options, static function ($a, $b) {
                return strcasecmp($a['label'], $b['label']);
            });
        }
        return $this->options;
    }

    /**
     * Retrieve all channel constants
     *
     * @return array
     */
    private function getAllChannels(): array
    {
        return [
            self::ALIEXPRESS,
            self::ALLEGRO,
            self::AMAZON,
            self::ARISE,
            self::BESLIST,
            self::BOL,
            self::CDISCOUNT,
            self::CHANNABLE_SANDBOX,
            self::CHECK24,
            self::EBAY,
            self::FNAC,
            self::FRUUGO,
            self::GALAXUS,
            self::HOMEDECO,
            self::IDEALO,
            self::KAUFLAND,
            self::MANOMANO,
            self::MIINTO,
            self::MIINTO_NEW,
            self::MIRAKL_ANWB,
            self::MIRAKL_BUT,
            self::MIRAKL_CARREFOUR,
            self::MIRAKL_CARREFOUR_FR,
            self::MIRAKL_CONFORAMA,
            self::MIRAKL_CONRAD,
            self::MIRAKL_COOLSHOP,
            self::MIRAKL_DARTY,
            self::MIRAKL_DEBENHAMS,
            self::MIRAKL_DECATHLON,
            self::MIRAKL_DOUGLAS,
            self::MIRAKL_EPRICE,
            self::MIRAKL_FONQ,
            self::MIRAKL_GALERIES_LAFAYETTE,
            self::MIRAKL_HOME24,
            self::MIRAKL_INNO,
            self::MIRAKL_KRUIDVAT,
            self::MIRAKL_LA_REDOUTE,
            self::MIRAKL_LE_BHV_MARAIS,
            self::MIRAKL_LEEN_BAKKER,
            self::MIRAKL_LEROY_MERLIN,
            self::MIRAKL_MAISONS_DU_MONDE,
            self::MIRAKL_MANOR,
            self::MIRAKL_MAXEDA_BRICO,
            self::MIRAKL_MAXEDA_PRAXIS,
            self::MIRAKL_MEDIAMARKT,
            self::MIRAKL_NEXTAIL,
            self::MIRAKL_NOCIBE,
            self::MIRAKL_OBELINK,
            self::MIRAKL_PCCOMPONENTES,
            self::MIRAKL_VTWONEN,
            self::MIRAKL_WORTEN,
            self::OTTO,
            self::RAKUTEN,
            self::REAL,
            self::SPARTOO,
            self::TIKTOK,
            self::TIKTOK_US,
            self::TOBEDRESSED,
            self::V_AND_D,
            self::VEEPEE,
            self::WALMART,
            self::WAYFAIR,
            self::WISH,
            self::ZALANDO,
        ];
    }

    /**
     * Get the display name of a channel based on its value
     *
     * @param string $value
     * @return string
     */
    public function getChannelName(string $value): string
    {
        return ucfirst(str_replace('_', ' ', str_replace('mirakl_', '', $value)));
    }
}