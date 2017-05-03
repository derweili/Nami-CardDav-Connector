<?php

use Sabre\VObject;


/**
* 
*/
class Nami_To_Card_Dav
{


	private $credentials;
	private $nami;
	private $carddav;
	private $members;
	
	function __construct( $credentials )
	{
        echo 'set credentials <br>';
		$this->credentials = $credentials;
        echo 'init_nami_connection <br>';
		$this->init_nami_connection();
        echo 'init_card_dav_backend <br>';
		$this->init_card_dav_backend();

        echo 'remove_all_contacts <br>';
		$this->remove_all_contacts();
        echo 'load_members_from_nami <br>';
		$this->load_members_from_nami();
        echo 'push_members_to_carddav <br>';
		$this->push_members_to_carddav();
	}


	private function init_nami_connection() {
		$this->nami = new NamiConnector(true, 'nami.dpsg.de', $this->credentials["cookie_path"]);

		$this->nami->login( array(
		
		    "username"  => $this->credentials["nami_user"],
		    "password"  => $this->credentials["nami_password"]
		
		    )
		);

		$this->nami->set_group_id( $this->credentials["nami_group_id"] );

	}

	private function init_card_dav_backend() {
		$this->carddav = new carddav_backend( $this->credentials["carddav_address"] );
		$this->carddav->set_auth( $this->credentials["carddav_user"], $this->credentials["carddav_password"] );

		//var_dump($this->carddav->check_connection());
	}


	private function remove_all_contacts() {
		
		$carddav_xml = $this->carddav->get();
		$carddav_array = simplexml_load_string($carddav_xml);

		foreach ($carddav_array->element as $carddav_item) {
			echo $carddav_item->id;
			echo "<br />";
			echo $this->carddav->delete( $carddav_item->id );
			echo "All contacts have been deleted";
		}

	}

	private function load_members_from_nami() {
		$this->members = $this->nami->get_members( $this->credentials["members_filter_string"], $this->credentials["members_search_string"] );
	}

	private function push_members_to_carddav() {
        //var_dump($this->members[0]);
		foreach ($this->members as $member) {

			if ( "Mitglied" == $member["entries_mglType"] ) {

				$new_member_vcard = $this->generate_vcard_from_member($member);
                echo 'svg card<br>';
                var_dump($new_member_vcard);
				$return = $this->carddav->add( $new_member_vcard );
                //echo 'add card return';
                var_dump($return);

			}

		}
	}


	private function generate_vcard_from_member( $member ) {
		$detailed_memberdata = $this->nami->get_detailed_memberdata($member["id"]);
        echo 'detailed data <br>';
        var_dump($detailed_memberdata);
		$bday = strtotime ( $detailed_memberdata["geburtsDatum"] );


        $vcard = new VObject\Component\VCard([
            'FN'  => $detailed_memberdata["vorname"] . ' ' . $detailed_memberdata["nachname"],
            //'TEL' => $member["entries"]["telefon2"],
            'N'   => [ $detailed_memberdata["nachname"], $detailed_memberdata["vorname"], '', '', ''],
            'ADR' => [ 
            	'',
                $detailed_memberdata["nameZusatz"], 
                $detailed_memberdata["strasse"], 
                $detailed_memberdata["ort"], 
                $detailed_memberdata["region"], 
                $detailed_memberdata["plz"], 
                $detailed_memberdata["land"]
            ],
            'BDAY' => date('Y-m-d', $bday),
        ]);

        $vcard->add(
            'TEL', 
            $detailed_memberdata["telefon1"], 
            [
                'type' => ['home']
            ]
        );


        $vcard->add(
            'TEL', 
            $detailed_memberdata["telefon2"], 
            [
                'type' => ['cell', 'voice']
            ]
        );

        $vcard->add(
            'TEL', 
            $detailed_memberdata["telefon3"], 
            [
                'type' => ['work', 'voice']
            ]
        );

        $vcard->add(
            'TEL', 
            $detailed_memberdata["telefax"], 
            [
                'type' => ['fax', 'home']
            ]
        );

        $vcard->add(
            'EMAIL', 
            $detailed_memberdata["email"], 
            [
                'type' => ['internet', 'home']
            ]
        );


        $vcard->add(
            'ITEM1.EMAIL', 
            $detailed_memberdata["emailVertretungsberechtigter"], 
            [
                'type' => ['internet']
            ]
        );
        $vcard->add(
            'ITEM1.X-ABLABEL', 
            'Vertretungsberechtigter'
        );




        return $vcard->serialize();


	}


}