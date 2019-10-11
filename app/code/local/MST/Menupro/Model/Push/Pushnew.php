<?php
class MST_Menupro_Model_Push_Pushnew extends MST_Menupro_Model_Categories
{
	public function getPushnewAutoSub ($categoryId, $tree, $autoShowSub = null)
	{
		if ($autoShowSub == null) {
			return false;
		}
		if (!is_numeric($categoryId)) {
			return false;
		}
		$pushnewBlock = new MST_Menupro_Block_Pushnew();
		$backLink = $pushnewBlock::$backLink;
		$helper = Mage::helper('menupro');
		$isDevelopMode = $helper->isDevelopMode();
		//Check current url is secure or not
		$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
		$validUrl = Mage::helper('menupro')->getValidUrl($baseUrl, Mage::app()->getStore()->isCurrentlySecure());
		$baseUrl = $validUrl;
		
		$children = $tree[$categoryId]['children'];
		if ($children == "") {
			return;
		}
		$childIds = explode(',', $children);
		$html = "";
		$html .= "<ul>";
		foreach ($childIds as $childId) {
			//-----------------Level 1-----------------------
			$child = $tree[$childId];
			//Update url_path in enterprise version
			if (!isset($child['url_path'])) {
				$newUrlPath = Mage::getModel('catalog/category')->load($child['entity_id'])->getUrl();
				$child['url_path'] = str_replace($baseUrl, '', $newUrlPath);
			}
			$isShow = false;
			$childIds = $this->getChildIds($tree, $childId);
			$liClass = (count($childIds)) ? "autosub-item " . $pushnewBlock::$hasChildIcon . " " : "autosub-item ";
			if ($child['include_in_menu'] == 1 && $child['is_active'] == 1) {
				$isShow = true;
			}
			if ($isShow) {
				$html .= "<li class='" . $liClass . "'>";
				if ($isDevelopMode) {
					$html .= '<a title="'. $helper->__($child['name']) . '" href="' . $baseUrl . $child['url_path']  . '">' . $helper->__($child['name']) . '</a>';
				} else {
					$categoryName = htmlentities($child['name']);
					$html .= '<a title="<?php echo $this->__("'. $categoryName .'") ?>" href="<?php echo $this->getMCPUrl()?>' . $child['url_path'] .'"><?php echo $this->__("'. $categoryName .'") ?></a>';
				}
				if ( count($childIds) > 0) {
					//--------Level 2----------
					$html .= "<div class='mp-level'>";
					$html .= "<h2>".$helper->__($child['name'])."</h2>";
					$html .= $backLink;
					$html .= "<ul>";
					foreach($childIds as $childId) {
						$child = $tree[$childId];
						if (!isset($child['url_path'])) {
							$newUrlPath = Mage::getModel('catalog/category')->load($child['entity_id'])->getUrl();
							$child['url_path'] = str_replace($baseUrl, '', $newUrlPath);
						}
						$isShow = false;
						$childIds = $this->getChildIds($tree, $childId);
						$liClass = (count($childIds)) ? "autosub-item " . $pushnewBlock::$hasChildIcon . " " : "autosub-item ";
						if ($child['include_in_menu'] == 1 && $child['is_active'] == 1) {
							$isShow = true;
						}
						if ($isShow) {
							$html .= "<li class='level3 " . $liClass . "'>";
                            if ($isDevelopMode) {
								$html .= '<a title="'. $helper->__($child['name']) . '" href="' . $baseUrl . $child['url_path']  . '">' . $helper->__($child['name']) . '</a>';
							} else {
								$categoryName = htmlentities($child['name']);
								$html .= '<a title="<?php echo $this->__("'. $categoryName .'") ?>" href="<?php echo $this->getMCPUrl()?>' . $child['url_path'] .'"><?php echo $this->__("'. $categoryName .'") ?></a>';
							}
							// ------------- Level 3---------------
							if ( count($childIds) > 0) {
								$html .= "<div class='mp-level'>";
								$html .= "<h2>".$helper->__($child['name'])."</h2>";
								$html .= $backLink;
								$html .= "<ul>";
								foreach($childIds as $childId) {
									$child = $tree[$childId];
									if (!isset($child['url_path'])) {
										$newUrlPath = Mage::getModel('catalog/category')->load($child['entity_id'])->getUrl();
										$child['url_path'] = str_replace($baseUrl, '', $newUrlPath);
									}
									$isShow = false;
									$childIds = $this->getChildIds($tree, $childId);
									$liClass = (count($childIds)) ? "autosub-item " . $pushnewBlock::$hasChildIcon . " " : "autosub-item ";
									$ulClass = $dataHover = $arrow = '';
									if ($child['include_in_menu'] == 1 && $child['is_active'] == 1) {
										$isShow = true;
									}
									if ($isShow) {
										$html .= "<li class='" . $liClass . "'>";
                                        if ($isDevelopMode) {
											$html .= '<a title="'. $helper->__($child['name']) . '" href="' . $baseUrl . $child['url_path']  . '">' . $helper->__($child['name']) . '</a>';
										} else {
											$categoryName = htmlentities($child['name']);
											$html .= '<a title="<?php echo $this->__("'. $categoryName .'") ?>" href="<?php echo $this->getMCPUrl()?>' . $child['url_path'] .'"><?php echo $this->__("'. $categoryName .'") ?></a>';
										}
										// ------------- Level 4---------------
										if ( count($childIds) > 0) {
											$html .= "<div class='mp-lvel'>";
											$html .= "<h2>".$helper->__($child['name'])."</h2>";
											$html .= $backLink;
											$html .= "<ul>";
											foreach($childIds as $childId) {
												$child = $tree[$childId];
												if (!isset($child['url_path'])) {
													$newUrlPath = Mage::getModel('catalog/category')->load($child['entity_id'])->getUrl();
													$child['url_path'] = str_replace($baseUrl, '', $newUrlPath);
												}
												$isShow = false;
												$childIds = $this->getChildIds($tree, $childId);
												$liClass = (count($childIds)) ? "autosub-item " . $pushnewBlock::$hasChildIcon . " " : "autosub-item ";
												
												if ($child['include_in_menu'] == 1 && $child['is_active'] == 1) {
													$isShow = true;
												}
												if ($isShow) {
													$html .= "<li class='" . $liClass . "'>";
                                                    if ($isDevelopMode) {
														$html .= '<a title="'. $helper->__($child['name']) . '" href="' . $baseUrl . $child['url_path']  . '">' . $helper->__($child['name']) . '</a>';
													} else {
														$categoryName = htmlentities($child['name']);
														$html .= '<a title="<?php echo $this->__("'. $categoryName .'") ?>" href="<?php echo $this->getMCPUrl()?>' . $child['url_path'] .'"><?php echo $this->__("'. $categoryName .'") ?></a>';
													}
													$html .= "</li>";
												}
											}
											$html .= "</ul>";
											$html .= "</div>";
										}
										// ------------- Level 4---------------
										$html .= "</li>";
									}
								}
								$html .= "</ul>";
								$html .= "</div>";
								// ------------- Level 3---------------
							}
							$html .= "</li>";
						}
					}
					$html .= "</ul>";
					$html .= "</div>";
					//--------Level 2----------
				}
				$html .= "</li>";
			}
			//-----------------End Level 1-------------------
		}
		$html .= "</ul>";
		return $html;
	}
}