<?php
/**
 * Kenya counties data with coordinates and major towns.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RW_Counties {

    /**
     * Get all 47 Kenya counties with coordinates and major towns.
     */
    public static function get_all() {
        return array(
            'baringo'       => array( 'name' => 'Baringo', 'lat' => 0.4919, 'lng' => 35.9430, 'towns' => array( 'Kabarnet', 'Eldama Ravine', 'Marigat', 'Mogotio' ) ),
            'bomet'         => array( 'name' => 'Bomet', 'lat' => -0.7813, 'lng' => 35.3416, 'towns' => array( 'Bomet', 'Sotik', 'Longisa', 'Silibwet' ) ),
            'bungoma'       => array( 'name' => 'Bungoma', 'lat' => 0.5695, 'lng' => 34.5584, 'towns' => array( 'Bungoma', 'Webuye', 'Kimilili', 'Malakisi' ) ),
            'busia'         => array( 'name' => 'Busia', 'lat' => 0.4608, 'lng' => 34.1108, 'towns' => array( 'Busia', 'Malaba', 'Nambale', 'Funyula' ) ),
            'elgeyo_marakwet' => array( 'name' => 'Elgeyo-Marakwet', 'lat' => 0.6783, 'lng' => 35.5079, 'towns' => array( 'Iten', 'Kapsowar', 'Cheptongei', 'Tambach' ) ),
            'embu'          => array( 'name' => 'Embu', 'lat' => -0.5389, 'lng' => 37.4596, 'towns' => array( 'Embu', 'Runyenjes', 'Siakago', 'Ishiara' ) ),
            'garissa'       => array( 'name' => 'Garissa', 'lat' => -0.4532, 'lng' => 39.6461, 'towns' => array( 'Garissa', 'Dadaab', 'Ijara', 'Balambala' ) ),
            'homa_bay'      => array( 'name' => 'Homa Bay', 'lat' => -0.5273, 'lng' => 34.4571, 'towns' => array( 'Homa Bay', 'Oyugis', 'Mbita', 'Kendu Bay' ) ),
            'isiolo'        => array( 'name' => 'Isiolo', 'lat' => 0.3546, 'lng' => 37.5822, 'towns' => array( 'Isiolo', 'Merti', 'Garbatulla', 'Kinna' ) ),
            'kajiado'       => array( 'name' => 'Kajiado', 'lat' => -2.0981, 'lng' => 36.7820, 'towns' => array( 'Kajiado', 'Ngong', 'Kitengela', 'Ongata Rongai', 'Kiserian' ) ),
            'kakamega'      => array( 'name' => 'Kakamega', 'lat' => 0.2827, 'lng' => 34.7519, 'towns' => array( 'Kakamega', 'Mumias', 'Butere', 'Malava' ) ),
            'kericho'       => array( 'name' => 'Kericho', 'lat' => -0.3692, 'lng' => 35.2863, 'towns' => array( 'Kericho', 'Litein', 'Londiani', 'Kipkelion' ) ),
            'kiambu'        => array( 'name' => 'Kiambu', 'lat' => -1.1714, 'lng' => 36.8356, 'towns' => array( 'Kiambu', 'Thika', 'Ruiru', 'Juja', 'Limuru', 'Kikuyu', 'Gatundu' ) ),
            'kilifi'        => array( 'name' => 'Kilifi', 'lat' => -3.5107, 'lng' => 39.9093, 'towns' => array( 'Kilifi', 'Malindi', 'Watamu', 'Mtwapa', 'Mariakani' ) ),
            'kirinyaga'     => array( 'name' => 'Kirinyaga', 'lat' => -0.4989, 'lng' => 37.2803, 'towns' => array( 'Kerugoya', 'Kutus', 'Sagana', 'Wanguru' ) ),
            'kisii'         => array( 'name' => 'Kisii', 'lat' => -0.6817, 'lng' => 34.7668, 'towns' => array( 'Kisii', 'Ogembo', 'Suneka', 'Keroka' ) ),
            'kisumu'        => array( 'name' => 'Kisumu', 'lat' => -0.0917, 'lng' => 34.7680, 'towns' => array( 'Kisumu', 'Ahero', 'Maseno', 'Muhoroni' ) ),
            'kitui'         => array( 'name' => 'Kitui', 'lat' => -1.3667, 'lng' => 38.0106, 'towns' => array( 'Kitui', 'Mwingi', 'Mutomo', 'Kabati' ) ),
            'kwale'         => array( 'name' => 'Kwale', 'lat' => -4.1816, 'lng' => 39.4521, 'towns' => array( 'Kwale', 'Ukunda', 'Diani', 'Msambweni', 'Lungalunga' ) ),
            'laikipia'      => array( 'name' => 'Laikipia', 'lat' => 0.3606, 'lng' => 36.7820, 'towns' => array( 'Nanyuki', 'Nyahururu', 'Rumuruti', 'Dol Dol' ) ),
            'lamu'          => array( 'name' => 'Lamu', 'lat' => -2.2717, 'lng' => 40.9020, 'towns' => array( 'Lamu', 'Mpeketoni', 'Witu', 'Mokowe' ) ),
            'machakos'      => array( 'name' => 'Machakos', 'lat' => -1.5177, 'lng' => 37.2634, 'towns' => array( 'Machakos', 'Athi River', 'Kangundo', 'Tala', 'Matuu' ) ),
            'makueni'       => array( 'name' => 'Makueni', 'lat' => -1.8039, 'lng' => 37.6200, 'towns' => array( 'Wote', 'Sultan Hamud', 'Emali', 'Makindu', 'Mtito Andei' ) ),
            'mandera'       => array( 'name' => 'Mandera', 'lat' => 3.9373, 'lng' => 41.8569, 'towns' => array( 'Mandera', 'Elwak', 'Rhamu', 'Takaba' ) ),
            'marsabit'      => array( 'name' => 'Marsabit', 'lat' => 2.3284, 'lng' => 37.9900, 'towns' => array( 'Marsabit', 'Moyale', 'Laisamis', 'Loiyangalani' ) ),
            'meru'          => array( 'name' => 'Meru', 'lat' => 0.0480, 'lng' => 37.6559, 'towns' => array( 'Meru', 'Maua', 'Nkubu', 'Timau', 'Chogoria' ) ),
            'migori'        => array( 'name' => 'Migori', 'lat' => -1.0634, 'lng' => 34.4731, 'towns' => array( 'Migori', 'Rongo', 'Awendo', 'Isebania' ) ),
            'mombasa'       => array( 'name' => 'Mombasa', 'lat' => -4.0435, 'lng' => 39.6682, 'towns' => array( 'Mombasa', 'Nyali', 'Bamburi', 'Likoni', 'Changamwe' ) ),
            'muranga'       => array( 'name' => "Murang'a", 'lat' => -0.7840, 'lng' => 37.0400, 'towns' => array( "Murang'a", 'Kangema', 'Maragua', 'Kenol' ) ),
            'nairobi'       => array( 'name' => 'Nairobi', 'lat' => -1.2921, 'lng' => 36.8219, 'towns' => array( 'Nairobi CBD', 'Westlands', 'Karen', 'Eastleigh', 'Kilimani', 'Langata', 'Kasarani' ) ),
            'nakuru'        => array( 'name' => 'Nakuru', 'lat' => -0.3031, 'lng' => 36.0800, 'towns' => array( 'Nakuru', 'Naivasha', 'Gilgil', 'Molo', 'Njoro', 'Subukia' ) ),
            'nandi'         => array( 'name' => 'Nandi', 'lat' => 0.1836, 'lng' => 35.1269, 'towns' => array( 'Kapsabet', 'Nandi Hills', 'Mosoriot', 'Kobujoi' ) ),
            'narok'         => array( 'name' => 'Narok', 'lat' => -1.0876, 'lng' => 35.8600, 'towns' => array( 'Narok', 'Kilgoris', 'Ololulung\'a', 'Mai Mahiu' ) ),
            'nyamira'       => array( 'name' => 'Nyamira', 'lat' => -0.5633, 'lng' => 34.9340, 'towns' => array( 'Nyamira', 'Keroka', 'Nyansiongo', 'Ekerenyo' ) ),
            'nyandarua'     => array( 'name' => 'Nyandarua', 'lat' => -0.1804, 'lng' => 36.5230, 'towns' => array( 'Ol Kalou', 'Engineer', 'Ndaragwa', 'Olkalou' ) ),
            'nyeri'         => array( 'name' => 'Nyeri', 'lat' => -0.4197, 'lng' => 36.9511, 'towns' => array( 'Nyeri', 'Karatina', 'Othaya', 'Mukurweini', 'Naro Moru' ) ),
            'samburu'       => array( 'name' => 'Samburu', 'lat' => 1.2156, 'lng' => 36.9541, 'towns' => array( 'Maralal', 'Archer\'s Post', 'Baragoi', 'Wamba' ) ),
            'siaya'         => array( 'name' => 'Siaya', 'lat' => -0.0617, 'lng' => 34.2422, 'towns' => array( 'Siaya', 'Bondo', 'Ugunja', 'Yala' ) ),
            'taita_taveta'  => array( 'name' => 'Taita-Taveta', 'lat' => -3.3961, 'lng' => 38.5650, 'towns' => array( 'Voi', 'Wundanyi', 'Taveta', 'Mwatate' ) ),
            'tana_river'    => array( 'name' => 'Tana River', 'lat' => -1.6508, 'lng' => 39.6537, 'towns' => array( 'Hola', 'Garsen', 'Kipini', 'Bura' ) ),
            'tharaka_nithi' => array( 'name' => 'Tharaka-Nithi', 'lat' => -0.3072, 'lng' => 37.7238, 'towns' => array( 'Chuka', 'Chogoria', 'Marimanti', 'Kathwana' ) ),
            'trans_nzoia'   => array( 'name' => 'Trans-Nzoia', 'lat' => 1.0567, 'lng' => 34.9507, 'towns' => array( 'Kitale', 'Endebess', 'Kwanza', 'Kiminini' ) ),
            'turkana'       => array( 'name' => 'Turkana', 'lat' => 3.3122, 'lng' => 35.5658, 'towns' => array( 'Lodwar', 'Kakuma', 'Lokichogio', 'Kalokol' ) ),
            'uasin_gishu'   => array( 'name' => 'Uasin Gishu', 'lat' => 0.5143, 'lng' => 35.2698, 'towns' => array( 'Eldoret', 'Burnt Forest', 'Turbo', 'Moiben' ) ),
            'vihiga'        => array( 'name' => 'Vihiga', 'lat' => 0.0839, 'lng' => 34.7078, 'towns' => array( 'Vihiga', 'Mbale', 'Luanda', 'Chavakali' ) ),
            'wajir'         => array( 'name' => 'Wajir', 'lat' => 1.7471, 'lng' => 40.0573, 'towns' => array( 'Wajir', 'Habaswein', 'Bute', 'Griftu' ) ),
            'west_pokot'    => array( 'name' => 'West Pokot', 'lat' => 1.6200, 'lng' => 35.1200, 'towns' => array( 'Kapenguria', 'Makutano', 'Chepareria', 'Ortum' ) ),
        );
    }

    /**
     * Search counties by name.
     */
    public static function search( $query ) {
        $results  = array();
        $counties = self::get_all();
        $query    = strtolower( $query );

        foreach ( $counties as $key => $county ) {
            if ( strpos( strtolower( $county['name'] ), $query ) !== false ) {
                $results[ $key ] = $county;
            }
        }

        return $results;
    }

    /**
     * Get a single county by key.
     */
    public static function get( $key ) {
        $counties = self::get_all();
        return isset( $counties[ $key ] ) ? $counties[ $key ] : null;
    }
}
