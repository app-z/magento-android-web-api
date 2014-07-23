<?php
/* License
*
*   This software is distributed under the [GNU GPL V3](http://www.gnu.org/licenses/gpl.html) License.
*
*/


//ini_set('display_errors', 1);


//
//	Set key for access to functionality this script
//
define('WEB_API_KEY', 'key1');







require_once('./app/Mage.php');
Mage::app();


$key = Mage::app()->getRequest()->getParam('key');

if ( !isset($key) || $key != WEB_API_KEY ) {

	$json = array('success' => false, 'code' => 20, 'message' => 'Invalid secret key');
	print_r(json_encode($json));


}elseif( Mage::app()->getRequest()->getParam('route') == "feed/web_api/categories" ){

	# -- $_GET params ------------------------------
	$parent = Mage::app()->getRequest()->getParam('parent', 0);
	$level = Mage::app()->getRequest()->getParam('level', 1);
	# -- End $_GET params --------------------------
	print_r(json_encode(getCategoryTree($parent, $level)));


}elseif(Mage::app()->getRequest()->getParam('route') == "feed/web_api/products"){

	# -- $_GET params ------------------------------
	$category_id = Mage::app()->getRequest()->getParam('category', 0);
	# -- End $_GET params --------------------------
	print_r(json_encode(products($category_id)));


}elseif(Mage::app()->getRequest()->getParam('route') == "feed/web_api/product"){

	# -- $_GET params ------------------------------
	$product_id = Mage::app()->getRequest()->getParam('id', 0);
	# -- End $_GET params --------------------------
	print_r(json_encode(product($product_id)));
}



//
//	Product Item
//	
//	http://localhost/magento/web-api.php?route=feed/web_api/product&id=800&key=key1
//
function product($product_id){
	$json = array('success' => true);

	$product = Mage::getModel('catalog/product')->load($product_id);

	$json['id'] = $product->getId();
	$json['name'] = $product->getName();
	$json['price'] = Mage::helper('core')->currency($product->getPrice(), true, false);
	$json['description'] = $product->getDescription();
	$json['image'] = (string)Mage::helper('catalog/image')->init($product, 'image');

	$mediaGallery = Mage::getModel('catalog/product')->load($product->getId())->getMediaGalleryImages()->getItems();
	$json['images'] = array();
        //loop through the images
        foreach ($mediaGallery as $image){
            $json['images'][] = $image['url'];
        }
	return $json;
}


//
//	Products in category
//
//	http://localhost/magento/web-api.php?route=feed/web_api/products&category=4&key=key1
//
function products($category_id){

	$json = array('success' => true, 'products' => array());

	$category = Mage::getModel ('catalog/category')->load($category_id);

	$products = Mage::getResourceModel('catalog/product_collection')
		          // ->addAttributeToSelect('*')
		          ->AddAttributeToSelect('name')
		          ->addAttributeToSelect('price')
		          ->addFinalPrice()
		          ->addAttributeToSelect('small_image')
		          ->addAttributeToSelect('image')
		          ->addAttributeToSelect('thumbnail')
		          ->addAttributeToSelect('short_description')
		          ->addUrlRewrite()
		          ->AddCategoryFilter($category);

	Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($products);

	$currencyCode = Mage::app()->getStore()->getBaseCurrencyCode();


	foreach($products as $product){ 
		$json['products'][] = array(
				'id'                    => $product->getId(),
				'name'                  => $product->getName(),
				'description'           => $product->getShortDescription(),
				'pirce'                 => Mage::helper('core')->currency($product->getPrice(), true, false), //." ".$currencyCode,
				'href'                  => $product->getProductUrl(),
				'thumb'                 => (string)Mage::helper('catalog/image')->init($product, 'thumbnail')
			);
		echo $thumbnail;
	}
	return $json;
}








//
//	Categories
//
//	http://localhost/magento/web-api.php?route=feed/web_api/categories&parent=0&level=2&key=key1
//
function getCategoryTree( $parent = 0, $recursionLevel = 1 )
{
    if($parent == 0){
        $parent = Mage::app()->getStore()->getRootCategoryId();
    }else{
	$parent = Mage::getModel('catalog/category')->load($parent)->getId();
    }

     $tree = Mage::getResourceModel('catalog/category_tree');
    /* @var $tree Mage_Catalog_Model_Resource_Category_Tree */

    $nodes = $tree->loadNode($parent)
        ->loadChildren($recursionLevel)
        ->getChildren();
    $tree->addCollectionData(null, false, $parent);

    $categoryTreeData = array();

    $json = array('success' => true);

    $result = array();


    foreach ($nodes as $node) {

        $result[] = array(
			'category_id'   => $node->getData('entity_id'),
			'parent_id'     => $parent,
			'name'          => $node->getData('name'),
			'categories'    => getNodeChildrenData($node));
    }

    $json['categories'] = $result;
    return $json;
}



function getNodeChildrenData(Varien_Data_Tree_Node $node)
{
    foreach ($node->getChildren() as $childNode) {
        $result[] = array(
			'category_id'   => $childNode->getData('entity_id'),
			'parent_id'     => $node->getData('entity_id'),
			'name'          => $node->getData('name'),
			'categories'    => getNodeChildrenData($childNode));


	 }
    return $result;
}


?>

