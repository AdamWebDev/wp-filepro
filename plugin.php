<?php
/*
Plugin Name: FilePro Connector
Plugin URI: http://norfolkcounty.ca
Description: Plugin to display files from FilePro
Version: 1.0
Author: Adam Wills
Author URI: http://adamwills.com
Author Email: webmaster@norfolkcounty.ca
License:

  Copyright 2013 Norfolk County (webmaster@norfolkcounty.ca)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  
*/

// TODO: rename this class to a proper name for your plugin
class FileProConnector {
	 
	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/
	
	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	public function __construct() {
		add_shortcode('filepro', array($this, 'show_files' ) );
		add_action('admin_menu', array($this, 'admin_menu') );
	} // end constructor
	

	public function show_files( $atts ) {
	   if( isset( $atts['id'] ) && $atts['id'] != '' && is_numeric( $atts['id'] ) ) {
	   	   $limit = ( isset( $atts['limit'] ) ? $atts['limit'] : 0 );
	   	   $showfolders = ( isset( $atts['showfolders'] ) ? $atts['showfolders'] : true );
	   	   $newfirst = ( isset( $atts['showfolders'] ) ? $atts['showfolders'] : true );
	   	   $iterate = ( isset( $atts['iterate'] ) ? $atts['iterate'] : false );
	   	   $mastercount = ( isset( $atts['mastercount'] ) ? $atts['mastercount'] : 0 );
		   $logon_result = $this->fplog_on('','');
		   $output="";
		   echo "<pre>";
		   print_r($atts);
		   echo $iterate;
		   echo "</pre>";
			if ($logon_result['success']) {
				$output.= $this->ShowFilesFromFilePro( $atts['id'], $logon_result['session_id'], $limit, $showfolders, $newfirst, $iterate, $mastercount );
			}
			else {
				$output.= "Unable to connect to FilePro";
			}
		   return $output;
		}
	}

	public function fplog_on($user_name, $password) {
		$results = array(
		'success' => FALSE,
		'session_id' => '',
		);
		$civicweb_url = 'https://norfolk.civicweb.net/';
		if (strlen($civicweb_url) > 0) {
			try {
				$client = @new SoapClient($civicweb_url . 'Global/WebServices/Login.asmx?wsdl', array('exceptions' => 1,));
				$parameters = new stdClass();
				$parameters->userName = $user_name;
				$parameters->password = $password;
				$retval = $client->LoginUser($parameters);
				$results['success'] = $retval->LoginUserResult;
				$results['session_id'] = $client->_cookies['CurrentSession'][0];
			}
			catch (SoapFault $E) {
				$results['success'] = FALSE;
				$results['session_id'] = '';
			}
		}
		return $results;
	}

	public function ShowFilesFromFilePro($id,$session_id,$limit=0,$showfolders=true,$newfirst=true,$iterate=false,$mastercount=0) {
		$output = "";
		$docs = array();
	
		$civicweb_url = get_option('wp_filepro_server');
		$client = @new SoapClient($civicweb_url . '/Global/WebServices/Document.asmx?wsdl', array('exceptions' => 1,));
		$client->__setCookie('CurrentSession', $session_id);
		$parameters = new stdClass();
		$parameters->id = $id;
		$parameters->path = "";
		$parameters->includeDocuments = true;
		$parameters->documentProvider = "iCompass.CivicWeb.Items.DocumentProvider";
		$parameters->controlID = "";
		
		$retval = $client->GetChildList($parameters);
		
		if (property_exists($retval->GetChildListResult, 'ID')) {
			$docs = $retval->GetChildListResult->Nodes->DocumentTreeNodeInformation;
		}
		else {
			$docs = array();
		}
	
		if(sizeof($docs)>0) {
			$output.= "<ul>";
			$docarray = array();
			if($limit==0 || $limit >= sizeof($docs)) $mylimit=sizeof($docs);
			else $mylimit = $limit;
			
			for ($i=0;$i<$mylimit;$i++) {
					$doc = $docs[$i];
					
				if($doc->Folder) {
					if($showfolders) {
						if($iterate) {
							$output.= '<li><a href="#">'.$doc->Name.'</a>';
						} 
						else {
							$output.= '<li><a href="' . $civicweb_url . 'Documents/DocumentList.aspx?ID=' . $doc->ID . '">'.$doc->Name.'</a>';	
						}
					}
					if($iterate) {
						$output.= $this->ShowFilesFromFilePro($doc->ID,$session_id,$limit,$showfolders,$newfirst,true,$mastercount);
					}
					if($showfolders) {
						$output.= '</li>';
					}
				} 
				else {
					$docarray[$i] = '<li><a href="' . $civicweb_url . 'Documents/DocumentDisplay.aspx?ID=' . $doc->ID . '&Original=1">'.$doc->Name.'</a></li>';
					$mastercount++;
				}
			}
			if($newfirst) {
					$docarray = array_reverse($docarray);
			}
			foreach($docarray as $doc ) {
				$output.= $doc;
			}
			$output.= "</ul>";
		}
		else {
			$output.= "<p>No documents found.</p>";
		}
	return $output;
	}// end action_method_name
	
	public function admin_menu () {
		add_options_page('WP-Filepro Settings','WP-Filepro','manage_options','wp_filepro_options',array( $this, 'settings_page' ) );
	}
	public function  settings_page () {
		include('views/admin.php');
	}

  
} // end class

// TODO:	Update the instantiation call of your plugin to the name given at the class definition
$plugin_name = new FileProConnector();