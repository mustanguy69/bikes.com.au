<?php
/**
* Magento Support Team.
* @category   MST
* @package    MST_Menupro
* @version    2.0
* @author     Magebay Developer Team <info@magebay.com>
* @copyright  Copyright (c) 2009-2013 MAGEBAY.COM. (http://www.magebay.com)
*/
class MST_Menupro_Block_Push extends MST_Menupro_Block_Menu {
	public static $backLink = null;
	public static $hasChildIcon = null;
	public function __construct() {
		parent::__construct();
		self::$backLink = '';
		self::$hasChildIcon = '';
		
	}
	public function getPushMenuHtml($groupId) {
		$isDisableFrontEnd = Mage::getStoreConfig('menupro/setting/enable');
		if ($isDisableFrontEnd == 0) {
			return;
		}
		/* Get Group Permission Id of current login */
		$permission = Mage::helper ( "menupro" )->authenticate ();
		$menuCollection = $this->getMenuCollection ( $groupId, $permission);
		$DHTML = "";
		if ($menuCollection != NULL) {
			$DHTML .= "<ul class='dl-menu'>";
			foreach ( $menuCollection as $menu ) {
				if ($menu->getParentId () == 0) {
					$menuData = $this->getMenuData ( $menu, $permission);
					if ($menu->getType() == 6) {
						$DHTML .= "<li><div class='static-block ". $menuData['liClasses'] ."'>" . $menuData ['block'] . "</div></li>";
						continue;
					};
					//MCP Level 0
					if (count($menuData['childcollection']) || $menuData['isAutoShowSub']) {
						$DHTML .= '<li class="'.self::$hasChildIcon.'">';
					} else {
						$DHTML .= '<li/>';
					}
					$DHTML .= self::getLink($menuData, $menu->getIconClass());
					if (count($menuData['childcollection'])) {
						$DHTML .= '<ul class="dl-submenu">';
							foreach ($menuData['childcollection'] as $menu) {
								if ($menu->getType() == 6) continue;
								$menuData = $this->getMenuData($menu, $permission);
								//MCP Level 1
								if (count($menuData['childcollection']) || $menuData['isAutoShowSub']) {
									$DHTML .= '<li class="'.self::$hasChildIcon.'">';
								} else {
									$DHTML .= '<li/>';
								}
								$DHTML .= self::getLink($menuData, $menu->getIconClass());
								if (count($menuData['childcollection'])) {
									$DHTML .= '<ul class="dl-submenu">';
										foreach ($menuData['childcollection'] as $menu) {
											if ($menu->getType() == 6) continue;
											$menuData = $this->getMenuData($menu, $permission);
											//MCP Level 2
											if (count($menuData['childcollection']) || $menuData['isAutoShowSub']) {
												$DHTML .= '<li class="'.self::$hasChildIcon.'">';
											} else {
												$DHTML .= '<li/>';
											}
											$DHTML .= self::getLink($menuData, $menu->getIconClass());
											if (count($menuData['childcollection'])) {
												$DHTML .= '<ul class="dl-submenu">';
													foreach ($menuData['childcollection'] as $menu) {
														if ($menu->getType() == 6) continue;
														$menuData = $this->getMenuData($menu, $permission);
														//MCP Level 3
														if (count($menuData['childcollection']) || $menuData['isAutoShowSub']) {
															$DHTML .= '<li class="'.self::$hasChildIcon.'">';
														} else {
															$DHTML .= '<li/>';
														}
														$DHTML .= self::getLink($menuData, $menu->getIconClass());
														if (count($menuData['childcollection'])) {
															$DHTML .= '<ul class="dl-submenu">';
																foreach ($menuData['childcollection'] as $menu) {
																	if ($menu->getType() == 6) continue;
																	$menuData = $this->getMenuData($menu, $permission);
																	//MCP Level 4
																	$DHTML .= '<li/>';
																	$DHTML .= self::getLink($menuData, $menu->getIconClass());
																	$DHTML .= "</li>";
																	//MCP Level 4
																}
															$DHTML .= '</ul>';
														} else {
															if ($menuData['isAutoShowSub']) {
																$autoSubMenu = $this->getPushAutoSub ( $menu->getUrlValue (), $menu->getAutosub ());
																if ($autoSubMenu != "") {
																	$DHTML .= $autoSubMenu;
																}
															}
														}
														$DHTML .= "</li>";
														//MCP Level 3
													}
												$DHTML .= '</ul>';
											} else {
												if ($menuData['isAutoShowSub']) {
													$autoSubMenu = $this->getPushAutoSub ( $menu->getUrlValue (), $menu->getAutosub ());
													if ($autoSubMenu != "") {
														$DHTML .= $autoSubMenu;
													}
												}
											}
											$DHTML .= "</li>";
											//MCP Level 2
										}
									$DHTML .= '</ul>';
								} else {
									if ($menuData['isAutoShowSub']) {
										$autoSubMenu = $this->getPushAutoSub ( $menu->getUrlValue (), $menu->getAutosub ());
										if ($autoSubMenu != "") {
											$DHTML .= $autoSubMenu;
										}
									}
								}
								$DHTML .= "</li>";
								//MCP Level 1
							}
						$DHTML .= '</ul>';
					} else {
						if ($menuData['isAutoShowSub']) {
							$autoSubMenu = $this->getPushAutoSub ( $menu->getUrlValue (), $menu->getAutosub ());
							if ($autoSubMenu != "") {
								$DHTML .= $autoSubMenu;
							}
						}
					}
					$DHTML .= "</li>";
					//End Level 0
				}
			}
			$DHTML .= "</ul>";
		}
		return $DHTML;
	}
	public static function  getLink($menuData, $iconClass) {
		$link = '<a class="' . $iconClass . '" title="' . $menuData['aTitle'] . '" target="' . $menuData['target'] . '" href="'.$menuData['aHref'].'">' . $menuData['aText'] . '</a>';
		return $link;
	}
	public function getStaticHtml($groupId) {
		$filename = $this->getMenuFilename($groupId, "push_");
		$path = $this->menuDesignDir();
		if (file_exists($path . $filename)) {
			$block = $this->getLayout()->createBlock('menupro/menu')->setTemplate("menupro/static/" . $filename)->toHtml();
		} else {
			//Create new static html file
			$menuHtml = $this->getPushMenuHtml($groupId);
			$response = $this->exportMenupro($menuHtml, $groupId, "push_");
			if ($response['success']) {
				$block = $this->getLayout()->createBlock('menupro/menu')->setTemplate("menupro/static/" . $response['filename'])->toHtml();
			}
		}
		return $block;
	}
	public function getPushAutoSub($categoryId, $isAutoShowSub) {
		$category = Mage::getModel('menupro/push_push');
		$html = $category->getPushAutoSub($categoryId, $this->_tree, $isAutoShowSub);
		return $html;
	}
}