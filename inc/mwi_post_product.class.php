<?php

class mwi_post_product {

	var $products = array();

	// PHP 4 Compatible Constructor
	public function mwi_post_product() {
		self::__construct();
	}
	
	// PHP 5 Constructor
	public function __construct() { 
		add_action( 'admin_menu', array(&$this, 'mwi_post_product_create_meta_box') );
		add_action( 'post_updated', array(&$this, 'mwi_post_product_save_post_meta_box') );
		add_action( 'admin_init', array(&$this, 'mage') );
	}  

  	public function layout() {
  		$app = self::getApp();
		$layout = $app->getLayout();
		$module = $app->getRequest()->getModuleName(); // Check if page belongs to Magento
		if(!$module) {
	  		$customerSession = Mage::getSingleton('customer/session');	
			$logged = ($customerSession->isLoggedIn()) ? 'customer_logged_in' : 'customer_logged_out';  
			$layout->getUpdate()
			    ->addHandle('default')
			    ->addHandle($logged)
			    ->load();
			$layout->generateXml()
			       ->generateBlocks();
		}
		return $layout;
  	} 
  	
  	public function getApp() {
	  	if(class_exists( 'Mage' ) && is_admin()) {
	  		$app = Mage::app(self::getValue('websitecode','base'), 'website');
	  		return $app;
	  	}
  	}
	
	public function getValue($key, $default = '') {		
		$options = get_option('mwi_options');	
		if (isset($options[$key])) {
			if($options[$key] == '') {
				return $default;
			} else {
				return $options[$key];
			}
		} else {
			return $default;
		}
	}
	
	public function mage() {
		// Mage Path
		$magepath = self::getValue('magepath');
		// Theme Info
		$package = self::getValue('package','default');
		$theme = self::getValue('theme','default');
		if ( !empty( $magepath ) && file_exists( $magepath ) && !is_dir( $magepath )) {
			require_once($magepath);
			umask(0);
			if(class_exists( 'Mage' ) && is_admin()) {
				$app = self::getApp();
				$locale = $app->getLocale()->getLocaleCode();
				Mage::getSingleton('core/translate')->setLocale($locale)->init('frontend', true);
				// Session setup
				Mage::getSingleton('core/session', array('name'=>'frontend'));
				Mage::getSingleton("checkout/session");
				// End session setups
				Mage::getDesign()->setPackageName($package)->setTheme($theme); // Set theme so Magento gets blocks from the right place.
			}
		}
		self::getProducts();
	}

	public function getProducts(){
		$collection = Mage::getModel('catalog/product')
		->getCollection()
		->addAttributeToSelect(array('name', 'sku'))
		->addAttributeToSort('name', 'ASC');;
		Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
		Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection); 
		foreach ($collection as $product) {
		  $name = $product->getName();
		  $sku = $product->getSku();
		  $products[] = array($name, $sku);
		}
		$this->products = $products;
	}

	public function mwi_post_product_create_meta_box(){
	  add_meta_box( 'mwi-post-product-meta-box', 'Select Product', array(&$this, 'mwi_post_product_meta_box'), 'post', 'normal', 'high' );
	}

	public function mwi_post_product_meta_box( $object, $box ) {
	  $selected_product[] = get_post_meta( $object->ID, 'mwi_post_product', true );
	  ?>
	  <div class="mwi-post-product-meta-box-content">
	    <div class="include-product" style="margin-bottom: 10px;">
	      <label for="mwi-post-product-include-product">
	      	<input style="margin-right: 5px;" type="checkbox" name="mwi-post-product-include-product" id="mwi-post-product-include-product" value="1" <?php if($selected_product[0][1] == 1){ echo "checked='checked'"; } ?>/>
	        Include a product at the end of the post
	      </label>
	    </div>
	    <div class="select-product">
	      <label for="select-a-product">Select a Product</label>
	      <br />
	      <select name="mwi-post-product-sku" id="mwi-post-product-sku">
	      	<option value="0" <?php if($selected_product[0][0] == 0){ echo 'selected'; } ?>>Store Products</option>
	      	<?php
	      	foreach($this->products as $product){ ?>
	      		<option value="<?php echo $product[1]; ?>" <?php if($selected_product[0][0] == $product[1]){ echo 'selected'; } ?>>
	      			<?php echo $product[0]; ?></option>
	      	<?php } ?>
	      </select>
	      <input type="hidden" name="mwi_post_product_meta_box_nonce" value="<?php echo wp_create_nonce( plugin_basename( __FILE__ ) ); ?>" />
	    </div>
	  </div>
	<?php 
	}
}