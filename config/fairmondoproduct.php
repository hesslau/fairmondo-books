***REMOVED***
***REMOVED***
    'fields' => [
        'title',
        'categories',
        'condition',
        'content',
        'quantity',
        'price_cents',
        'vat',
        'external_title_image_url',
        'transport_type1',
        'transport_type1_provider',
        'transport_type1_price_cents',
        'transport_type1_number',
        'transport_details',
        'transport_time',
        'unified_transport',
        'payment_bank_transfer',
        'payment_paypal',
        'payment_invoice',
        'payment_voucher',
        'payment_details',
        'gtin',
        'custom_seller_identifier',
        'action'
***REMOVED***,
    'default' => [
        'condition'                 => 'new',
        'transport_type1'           => true,
        'transport_type1_provider'  => 'Post/DHL',
        'transport_type1_price_cents'=> 300,
        'transport_type1_number'    => 9,
        'transport_details'         => '',
        'unified_transport'         => true,
        'payment_bank_transfer'     => true,
        'payment_paypal'            => true,
        'payment_invoice'           => false,
        'payment_voucher'           => true,
        'payment_details'           => ''
***REMOVED***,
    'maps' => [
        'ProductForm' => [
            'BA' => 'Buch',
            'BB' => 'Gebundenes Buch',
            'BC' => 'Taschenbuch',
            'BG' => 'Ledergebundenes Buch',
            'BH' => 'Gebundenes Buch',
            'BI' => 'Gebundenes Buch',
            'BP' => 'Buch',
            'BZ' => 'Buch',
            'AC' => 'Audio CD',     //27
            //'DB' => 'CD',           //27
            'DA' => 'MP3 CD',       //27
            'AI' => 'Audio DVD',    //28
            'VI' => 'Video DVD',    //24
            'VO' => 'Blue Ray',     //25
            'ZE' => 'Spiel',        //85
            'DG' => 'eBook',        //116
            'PC' => 'Kalender',     //1654
            //'00' => 'Hardware',     //1122
***REMOVED***
        // map Libri Product Form to a Fairmondo Category
        'ProductForm2FairmondoCategory' => [
            'BA' => 56,
            'BB' => 56,
            'BC' => 56,
            'BH' => 56,
            'BG' => 56,
            'BI' => 56,
            'BP' => 56,
            'BZ' => 56,
            'AC' => 27,
            'DB' => 27,
            'DA' => 27,
            'AI' => 28,
            'VI' => 24,
            'VO' => 25,
            'ZE' => 85,
            'DG' => 116,
            'PC' => 1654,
            '00' => 1122,
            'ZZ' => 1122    // assumption based on productformdescriptions
***REMOVED***
        'ProductLanguage' => [
             'ger' => 'Deutsch',
             'eng' => 'Englisch',
             'fre' => 'Französisch',
             'spa' => 'Spanisch',
             'ita' => 'Italienisch',
             'fin' => 'Finnisch',
             'tur' => 'Türkisch',
             'dan' => 'Dänisch'
***REMOVED***
        'ProductReferenceType' => [
            15  => 'EAN',
            03  => 'ISBN13',
            02  => 'ISBN10'
    ***REMOVED***
***REMOVED***,
    'conditions' => [
        'AvailabilityStatus' => [20,21,22],         // 20: Available, 21: In Stock, 22: To order, 23: Print on Demand; for more Info see ONIX Codelist 65
        'invalidAudienceCodeValues' => [16,17,18],
        'maxPriceCents' => 1000000
***REMOVED***,
    'templates' => [
        'DistinctiveTitle'  => '<h3>%s</h3>',
        'Author'            => '<p>von <b>%s</b></p>',
        'ProductLanguage'   => '%s, ',
        'NumberOfPages'     => '%s Seiten, ',
        'PublicationDate'   => '%s, ',
        'PublisherName'     => '%s, ',
        'ProductForm'       => '%s, ',
        'ProductReference'  => 'EAN %s',
        'Blurb'             => '<p><b>Beschreibung</b></p><p>%s</p>',
        'AudioBook'         => 'Hörbuch, '
***REMOVED***,
    'CustomSellerIdentifierTemplate' => 'LIB-%013s',
    'TitleTemplate' => "%Author%Title (%ProductForm%AudioBook%ProductReference)",
    'ContentTemplate' => '
                  %DistinctiveTitle%Author<p>
                    %ProductLanguage
                    %NumberOfPages
                    %PublicationDate
                    %AudioBook 
                    %PublisherName
                    %ProductForm
                    %ProductReference
                </p>
                <p>
                    %Blurb
                 </p>',
    'DigitalTemplate' => '%DistinctiveTitle%Author<p>%PublicationDate%PublisherName%ProductForm%ProductReference</p>%Blurb',
    'CoverLinkBaseUrl' => 'http://mitmachen.fairmondo.de:8080/media/',
    'AudiobookDescription' => 'Hörbuch',
    'Blacklist' => [
        "DistinctiveTitle" => ['Ron Hubbard'],
        "Author" => ['Jan van Helsing','Ron Hubbard'],
        "PublisherName" => ['Books LLC','Chronicle Books','Nabu Press','Winkelried Verlag'],
        "Blurb" => ['Source: Wikipedia','High Quality Content by WIKIPEDIA articles'],
        "ProductReference" => ['4005556186013', '3558380002666', '9783902778772', '9783902778789', '9783902778796', '9783902778802', '9783902778840', '9783902778857'
            , '9783990230220', '9783990230244', '9783902778765', '9783902778796', '9783902778802', '9783902778857'
            , '9783892915508', '9783456854427', '9781681766942', '9783898798907', '9783862486861', '9783862486854'
            , '4020628842833', '4020628842840', '9783664356508', '9783954981830', '9783959490047', '9783959490504'
            , '9783710400377', '9780875802381', '9783432102399', '9783432102412', '9783432102405']
***REMOVED***
    'ForbiddenCharacters' => [
        "\"" => "´",
        ";" => "",      // careful! semicolons will break the CSV! (@todo find solution)
        ">" => "",
        "<" => ""
***REMOVED***
***REMOVED***