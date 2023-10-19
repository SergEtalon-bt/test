<?php

namespace ControllerProducts;

use Controller\Controller;
use ModelProducts\ModelProducts;
use ModelSuppliers\ModelSuppliers;


class ControllerProducts extends Controller
{
	private $action;
	private $param;
	private $data = [];
	private $model_products;
	private $model_suppliers;

	public function __construct($action = '', $param = [])
	{
		$this->model_products = new ModelProducts();
		$this->model_suppliers = new ModelSuppliers();
		$this->action = $action;
		$this->param = $param;
	}

	public function getData()
	{
		$resProducts = $this->model_products->getData();
		$products = [];
		foreach ($resProducts as $product) {
			$resCodes = $this->model_suppliers->getCodesByProductId($product['id']);
			foreach ($resCodes as $code) {
				$supplier = $this->model_suppliers->getSupplier($code['supplier_id']);
				$product['codes'][] = [
					'supplier_name' => $supplier['name'],
					'article' => $code['article'],
				];
			}
			$products[] = $product;
		}

//			echo ' < pre>';
//			var_export($products);
//			echo ' </pre > ';
		$this->data['products'] = $products;
		if (!empty($this->action)) {
			$func = $this->action . 'Data';
			$this->$func();
		}


		return $this->data;
	}

	private function addData()
	{
		if (isset($_POST['button_add_product'])) {
			$this->add_product();
		}
		$this->data['suppliers'] = $this->model_suppliers->getSuppliers();
		$this->data['brands'] = $this->model_products->getBrands();
		$this->data['categories'] = $this->model_products->getCategories();
	}

	private function add_product()
	{
//		var_export($_POST);
		$newProduct['name'] = $_POST['form_add_name'];
		$newProduct['brand_id'] = $_POST['form_add_brand'];
		$newProduct['category_id'] = $_POST['form_add_category'];
		$product = $this->model_products->addProduct($newProduct);
//		var_export($product);
		$this->data['new_product'] = $product;

		if (isset($_POST['form_add_suppliers'])) {
			foreach ($_POST['form_add_suppliers'] as $key => $value) {
				$newCode = [
					'product_id' => $product['id'],
					'supplier_id' => $key,
					'article' => $value,
				];
				$this->model_suppliers->addCode($newCode);
				$supplier = $this->model_suppliers->getSupplier($key);
				$this->data['new_product']['code'][] = [
					'supplier_id' => $supplier['id'],
					'name' => $supplier['name'],
					'article' => $value,
				];
			}
		}

	}

	private function editData()
	{
		if (isset($_POST['button_edit_product'])) {
			$this->edit_product();
		}

		$this->data = [];

		$product = $this->model_products->getProduct($this->param['id']);
		$resCodes = $this->model_suppliers->getCodes($product['id']);
		$arrSuppliers = [];
		foreach ($resCodes as $code) {

				$supplier = $this->model_suppliers->getSupplier($code['supplier_id']);
				$price = $this->model_products->getPriceByProductIdAndSupplierId($product['id'], $supplier['id']);
				unset($price['id']);
				unset($price['product_id']);
				unset($price['supplier_id']);
				$arrSuppliers[$supplier['id']]['id'] = $supplier['id'];
				$arrSuppliers[$supplier['id']]['name'] = $supplier['name'];
				$arrSuppliers[$supplier['id']]['kod'] = $code['article'];
				$arrSuppliers[$supplier['id']]['price'] = $price;

				$warehouses = $this->model_suppliers->getSkladBySupplier($supplier['id']);
				if (!empty($warehouses)) {
					foreach ($warehouses as $warehouse) {
						$quantity = $this->model_products->getQuantity($product['id'], $warehouse['id']);
						$arrSuppliers[$supplier['id']]['warehouses'][] = [
							'id' => $warehouse['id'],
							'name' => $warehouse['name'],
							'quantity' => $quantity['quantity'] ?? '',
						];
					}
				}
				if (empty($code['article']) && empty($price['price']) && empty($price['rrc'])) {
					unset($arrSuppliers[$supplier['id']]);
				}
		}
		$suppliers = [];
		foreach ($arrSuppliers as $supplier) {
			$suppliers[] = $supplier;
		}
		$product['suppliers'] = $suppliers;

		$brand = $this->model_products->getBrandById($product['brand_id']);
		$product['brand'] = [
			'id' => $brand['id'],
			'name' => $brand['name'],
		];
		unset($product['brand_id']);

		$category = $this->model_products->getCategoryById($product['category_id']);
		$product['category'] = [
			'id' => $category['id'],
			'name' => $category['name'],
		];
		unset($product['category_id']);

		unset($product['date_create']);
		unset($product['date_change']);
		$data['product'] = $product;

		$brands = $this->model_products->getBrands();
		$data['brands'] = $brands;

		$categories = $this->model_products->getCategories();
		$data['categories'] = $categories;

		$resSuppliers = $this->model_suppliers->getSuppliers();
		$suppliers = [];
		foreach ($resSuppliers as $supplier) {
			$resCode = $this->model_suppliers->getCode($product['id'], $supplier['id']);
			$supplier['kod'] = $resCode['article'] ?? '';
			unset($supplier['data_load_price']);
			unset($supplier['param_parser']);

			$warehouses = $this->model_suppliers->getSkladBySupplier($supplier['id']);
			foreach ($warehouses as $warehouse) {
				unset($warehouse['supplier_id']);
				$quantity = $this->model_products->getQuantity($product['id'], $warehouse['id']);
				$warehouse['quantity'] = $quantity['quantity'] ?? '';
				$supplier['warehouses'][] = $warehouse;
			}

			$price = $this->model_products->getPriceByProductIdAndSupplierId($product['id'], $supplier['id']);
			if (!empty($price)) {
				unset($price['id']);
				unset($price['product_id']);
				unset($price['supplier_id']);
				$supplier['price'] = $price;
			} else {
				$supplier['price'] = [
					'price' => '',
					'rrc' => '',
				];
			}
			$suppliers[] = $supplier;
		}
		$data['suppliers'] = $suppliers;

		$data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS);
//		$data = preg_replace('/\\\\\'/', "\\\\\\'", $data);
		$this->data['data'] = $data;


	}

	private function edit_product()
	{
		$arProduct['id'] = $_POST['form_edit_id'];
		$arProduct['name'] = $_POST['form_edit_name'];
		$arProduct['brand_id'] = $_POST['form_edit_brand'];
		$arProduct['category_id'] = $_POST['form_edit_category'];

		$this->model_products->updateProduct($arProduct);

		$arSuppliers = $_POST['form_edit_suppliers'];

		$idCodes = [];
		$idQuantities = [];
		foreach ($arSuppliers as $supplier) {
			$arCode = [
				'product_id' => $arProduct['id'],
				'supplier_id' => $supplier['id'],
				'article' => $supplier['kod'],
			];
			$resCode = $this->model_suppliers->getCode($arProduct['id'], $supplier['id']);
			if (empty($resCode)) {
				$this->model_suppliers->addCode($arCode);
				$resCode = $this->model_suppliers->getCode($arProduct['id'], $supplier['id']);
			} else {
				$this->model_suppliers->updateCode($arCode);
			}
			$idCodes[] = $resCode['id'];

			foreach ($supplier['warehouses'] as $id => $quantity) {
				$arQuantity = [
					'product_id' => $arProduct['id'],
					'warehouse_id' => $id,
					'quantity' => $quantity,
				];
				$resQuantity = $this->model_products->getQuantity($arProduct['id'], $id);
				if (empty($resQuantity)) {
					$this->model_products->addQuantity($arQuantity);
					$resQuantity = $this->model_products->getQuantity($arProduct['id'], $id);
				} else {
					$this->model_products->updateQuantity($arQuantity);
				}
				$idQuantities[] = $resQuantity['id'];
			}

			$arPrice = [
				'product_id' => $arProduct['id'],
				'supplier_id' => $supplier['id'],
				'price' => $supplier['price']['price'] ?? '',
				'rrc' => $supplier['price']['rrc'] ?? '',
			];

			$resPrice = $this->model_products->getPriceByProductIdAndSupplierId($arProduct['id'], $supplier['id']);
			if (empty($resPrice)) {
				$this->model_products->addPrice($arPrice);
			} else {
				$this->model_products->updatePrice($arPrice);
			}
		}

		$arCodes = $this->model_suppliers->getCodesByProductId($arProduct['id']);
		foreach ($arCodes as $code) {
			if (in_array($code['id'], $idCodes)) continue;
			$this->model_suppliers->updateCodeById($code['id'], '');
		}

		$arQuantities = $this->model_products->getQuantitiesByProductId($arProduct['id']);
		foreach ($arQuantities as $quantity) {
			if (in_array($quantity['id'], $idQuantities)) continue;
			$this->model_products->updateQuantityById($quantity['id'], '');
		}

//		$product = $this->model_products->addProduct($newProduct);
//		var_export($product);
//		$this->data['new_product'] = $product;
//
//		if (isset($_POST['form_add_suppliers'])) {
//			foreach ($_POST['form_add_suppliers'] as $key => $value) {
//				$newCode = [
//					'product_id' => $product['id'],
//					'supplier_id' => $key,
//					'article' => $value,
//				];
//				$this->model_suppliers->addCode($newCode);
//				$supplier = $this->model_suppliers->getSupplier($key);
//				$this->data['new_product']['code'][] = [
//					'supplier_id' => $supplier['id'],
//					'name' => $supplier['name'],
//					'article' => $value,
//				];
//			}
//		}

	}

	public function getLayout()
	{
		if ($this->action == 'add') {
			$template = '/templates/products/TemplateAddProduct.php';
			$template = str_replace('\\', '/', $template);
			return $template;
		}
		if ($this->action == 'edit') {
			$template = '/templates/products/TemplateEditProduct.php';
			$template = str_replace('\\', '/', $template);
			return $template;
		}
		$template = '/templates/products/TemplateProducts.php';
		$template = str_replace('\\', '/', $template);
		return $template;
	}
}
