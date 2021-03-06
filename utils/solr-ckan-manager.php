<?php

 use Solarium\Solarium;
 use Solarium\Exception;
 use Solarium\Exception\HttpException;
 use Solarium\QueryType\Select\Query\Query as Select;

 include_once plugin_dir_path(__FILE__).'wp_odm_solr_options.php';

 $GLOBALS['wp_odm_solr_options'] = new WpOdmSolr_Options();
/*
 * OpenDev
 * Solr Manager
 */

class WP_Odm_Solr_CKAN_Manager {

  var $client = null;
  var $server_config = null;

	function __construct() {

    wp_odm_solr_log('solr-ckan-manager __construct');

    $solr_host = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_host');
    $solr_port = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_port');
    $solr_scheme = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_scheme');
    $solr_path = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_path');
    $solr_core_ckan = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_core_ckan');
    $solr_user = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_user');
    $solr_pwd = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_pwd');

    $this->server_config = array(
      'endpoint' => array(
          $solr_host => array(
              'host' => $solr_host,
              'port' => $solr_port,
              'path' => $solr_path,
  						'core' => $solr_core_ckan,
  						'scheme' => $solr_scheme,
              'username' => $solr_user,
              'password' => $solr_pwd
          )
      )
  	);

    try {
      $this->client = new \Solarium\Client($this->server_config);
    } catch (HttpException $e) {
      wp_odm_solr_log('solr-wp-manager __construct Error: ' . $e);
    }

	}

  function ping_server(){

    wp_odm_solr_log('solr-ckan-manager ping_server');

    if (!isset($this->client)):
      return false;
    endif;

    try {
      $ping = $this->client->createPing();
      $result = $this->client->ping($ping);
    } catch (HttpException $e) {
      wp_odm_solr_log('solr-wp-manager ping_server Error: ' . $e);
      return false;
    }

    return true;
  }

	function query($text, $typeFilter = null){

    wp_odm_solr_log('solr-ckan-manager query' . $text);

    $resultset = null;

    try {
      $query = $this->client->createSelect();
  		$query->setQuery($text);
  		if (isset($typeFilter)):
  			$query->createFilterQuery('dataset_type')->setQuery('type:' . $typeFilter);
  		endif;

      $current_country = odm_country_manager()->get_current_country();
      if ( $current_country != "mekong"):
        $current_country_code = odm_country_manager()->get_current_country_code();
  			$query->createFilterQuery('extras_odm_spatial_range')->setQuery('extras_odm_spatial_range:' . $current_country_code);
  		endif;

      $dismax = $query->getDisMax();
      $dismax->setQueryFields('title notes tags extras_odm_keywords');
      $dismax->setQueryFields('extras_odm_keywords^4 tags^3 title^2 notes^1');

  		$resultset = $this->client->select($query);
    } catch (HttpException $e) {
      wp_odm_solr_log('solr-wp-manager ping_server Error: ' . $e);
    }

		return $resultset;
	}

}

$GLOBALS['WP_Odm_Solr_CKAN_Manager'] = new WP_Odm_Solr_CKAN_Manager();

function WP_Odm_Solr_CKAN_Manager() {
	return $GLOBALS['WP_Odm_Solr_CKAN_Manager'];
}

?>
