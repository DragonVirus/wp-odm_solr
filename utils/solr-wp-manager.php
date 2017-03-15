<?php

 use Solarium\Solarium;
 use Solarium\QueryType\Select\Query\Query as Select;

 include_once plugin_dir_path(__FILE__).'wp_odm_solr_options.php';

 $GLOBALS['wp_odm_solr_options'] = new WpOdmSolr_Options();
/*
 * OpenDev
 * Solr Manager
 */

class WP_Odm_Solr_WP_Manager {

  var $client = null;
  var $server_config = null;

	function __construct() {

    wp_odm_solr_log('solr-wp-manager __construct');

    $solr_host = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_host');
    $solr_port = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_port');
    $solr_scheme = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_scheme');
    $solr_path = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_path');
    $solr_core_wp = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_core_wp');
    $solr_user = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_user');
    $solr_pwd = $GLOBALS['wp_odm_solr_options']->get_option('wp_odm_solr_setting_solr_pwd');

    $this->server_config = array(
      'endpoint' => array(
          'localhost' => array(
              'host' => $solr_host,
              'port' => $solr_port,
              'path' => $solr_path,
  						'core' => $solr_core_wp,
  						'scheme' => $solr_scheme
          )
      )
  	);

		$this->client = new \Solarium\Client($this->server_config);

		$options = get_option('odm_options');
		$solr_config = $options['solr_config'];
    $this->client->getEndpoint()->setAuthentication($solr_config['solr_user'],$solr_config['solr_pwd']);

	}

  function ping_server(){

    wp_odm_solr_log('solr-wp-manager ping_server');

    $ping = $this->client->createPing();

    try {
      $result = $this->client->ping($ping);
    } catch (Solarium\Exception $e) {
      return false;
    }

    return true;
  }

	function index_post($post){

    wp_odm_solr_log('solr-wp-manager index_post ' . print_r($post, true));

    $update = $this->client->createUpdate();

		$doc = $update->createDocument();
		$doc->id = $post->ID;
		$doc->blogid = get_current_blog_id();
		$doc->blogdomain = get_site_url();
		$doc->title = $post->post_title;
		$doc->permalink = get_permalink($post);
		$doc->author = $post->post_author;
		$doc->content = $post->post_content;
		$doc->excerpt = $post->post_excerpt;
		$doc->type = $post->post_type;
		$doc->categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
		$doc->tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
		$date = new DateTime($post->post_date);
		$doc->date = $date->format('Y-m-d\TH:i:s\Z');
		$modified = new DateTime($post->post_modified);
		$doc->modified = $modified->format('Y-m-d\TH:i:s\Z');
		$update->addDocument($doc);
		$update->addCommit();
		$result = $this->client->update($update);

    return $result;
  }

	function clear_index(){

    wp_odm_solr_log('solr-wp-manager clear_index');

		// get an update query instance
		$update = $this->client->createUpdate();

		// add the delete query and a commit command to the update query
		$update->addDeleteQuery('title:*');
		$update->addCommit();

		// this executes the query and returns the result
		$result = $this->client->update($update);

		return $result;
  }

	function query($text, $typeFilter = null){

    wp_odm_solr_log('solr-wp-manager query ' . $text);

		$query = $this->client->createSelect();
		$query->setQuery($text);
		if (isset($typeFilter)):
			$query->createFilterQuery('type')->setQuery('type:' . $typeFilter);
		endif;

    $current_country = odm_country_manager()->get_current_country();
    if ( $current_country != "mekong"):
			$query->createFilterQuery('country_site')->setQuery('country_site:' . $current_country);
		endif;

    $dismax = $query->getDisMax();
    $dismax->setQueryFields('title content categories tags');
    $dismax->setQueryFields('categories^3 title^2 content^1');

		$resultset = $this->client->select($query);
		return $resultset;
	}

}

$GLOBALS['WP_Odm_Solr_WP_Manager'] = new WP_Odm_Solr_WP_Manager();

function WP_Odm_Solr_WP_Manager() {
	return $GLOBALS['WP_Odm_Solr_WP_Manager'];
}

?>