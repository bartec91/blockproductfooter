<?php
if (!defined('_PS_VERSION_'))
	exit;

class BlockTextProductFooter extends Module
{
	public function __construct()
	{
		$this->name = 'blocktextproductfooter';
		$this->tab = 'front_office_features';
		$this->version = '1.0';
		$this->author = 'Bartlomiej Bakalarz';
		$this->need_instance = 0;

		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Block Text Product Footer');
		$this->description = $this->l('Displays a text and image at the footer of the product.');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
	}

	public function install()
	{
		return
			parent::install() &&
			$this->registerHook('displayFooterProduct') &&
			$this->registerHook('displayHeader') &&
			$this->registerHook('actionObjectLanguageAddAfter') &&
			$this->installFixtures();
	}

	public function hookActionObjectLanguageAddAfter($params)
	{
		return $this->installFixture((int)$params['object']->id, Configuration::get('BLOCKPRODUCTFOOTER_IMG', (int)Configuration::get('PS_LANG_DEFAULT')));
	}

	protected function installFixtures()
	{
		$languages = Language::getLanguages(false);
		foreach ($languages as $lang)
			$this->installFixture((int)$lang['id_lang'], 'exampleimage.png');

		return true;
	}

	protected function installFixture($id_lang, $image = null)
	{
		$values['BLOCKPRODUCTFOOTER_IMG'][(int)$id_lang] = $image;
		$values['BLOCKPRODUCTFOOTER_DESC'][(int)$id_lang] = '';
		$values['BLOCKPRODUCTFOOTER_EXCHANGE_TEXT'][(int)$id_lang] = '';
		Configuration::updateValue('BLOCKPRODUCTFOOTER_IMG', $values['BLOCKPRODUCTFOOTER_IMG']);
		Configuration::updateValue('BLOCKPRODUCTFOOTER_DESC', $values['BLOCKPRODUCTFOOTER_DESC']);
		Configuration::updateValue('BLOCKPRODUCTFOOTER_EXCHANGE_TEXT', $values['BLOCKPRODUCTFOOTER_EXCHANGE_TEXT']);
	}

	public function uninstall()
	{
		Configuration::deleteByName('BLOCKPRODUCTFOOTER_IMG');
		Configuration::deleteByName('BLOCKPRODUCTFOOTER_DESC');
		Configuration::deleteByName('BLOCKPRODUCTFOOTER_EXCHANGE_TEXT');
		return parent::uninstall();
	}

	public function hookDisplayFooterProduct($params)
	{
		if (!$this->isCached('blocktextproductfooter.tpl', $this->getCacheId()))
		{
			$imgname = Configuration::get('BLOCKPRODUCTFOOTER_IMG', $this->context->language->id);

			if ($imgname && file_exists(_PS_MODULE_DIR_.$this->name.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$imgname))
				$this->smarty->assign('blockproductfooter_img', $this->context->link->protocol_content.Tools::getMediaServer($imgname).$this->_path.'img/'.$imgname);

			$this->smarty->assign(array(
				'blockproductfooter_desc' => Configuration::get('BLOCKPRODUCTFOOTER_DESC', $this->context->language->id)
			));
		}

		return $this->display(__FILE__, 'blocktextproductfooter.tpl', $this->getCacheId());
	}

	public function hookDisplayHeader($params)
	{
		$this->context->controller->addCSS($this->_path.'blocktextproductfooter.css', 'all');

		$blockTextToExchange = Configuration::get('BLOCKPRODUCTFOOTER_EXCHANGE_TEXT', $this->context->language->id);
		if(!empty($blockTextToExchange))
		{
			$this->smarty->assign(array(
				'blockproductfooter_exchange_text' => $blockTextToExchange
			));
			Media::addJsDef(array('blockproductfooter_exchange_text' => $blockTextToExchange));
			$this->context->controller->addJS($this->_path.'blocktextproductfooter.js', 'all');
		}
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submitStoreConf'))
		{
			$languages = Language::getLanguages(false);
			$values = array();
			$update_images_values = false;

			foreach ($languages as $lang)
			{
				if (isset($_FILES['BLOCKPRODUCTFOOTER_IMG_'.$lang['id_lang']])
					&& isset($_FILES['BLOCKPRODUCTFOOTER_IMG_'.$lang['id_lang']]['tmp_name'])
					&& !empty($_FILES['BLOCKPRODUCTFOOTER_IMG_'.$lang['id_lang']]['tmp_name']))
				{
					if ($error = ImageManager::validateUpload($_FILES['BLOCKPRODUCTFOOTER_IMG_'.$lang['id_lang']], 4000000))
						return $error;
					else
					{
						$ext = substr($_FILES['BLOCKPRODUCTFOOTER_IMG_'.$lang['id_lang']]['name'], strrpos($_FILES['BLOCKPRODUCTFOOTER_IMG_'.$lang['id_lang']]['name'], '.') + 1);
						$file_name = md5($_FILES['BLOCKPRODUCTFOOTER_IMG_'.$lang['id_lang']]['name']).'.'.$ext;

						if (!move_uploaded_file($_FILES['BLOCKPRODUCTFOOTER_IMG_'.$lang['id_lang']]['tmp_name'], dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$file_name))
							return $this->displayError($this->l('An error occurred while attempting to upload the file.'));
						else
						{
							if (Configuration::hasContext('BLOCKPRODUCTFOOTER_IMG', $lang['id_lang'], Shop::getContext())
								&& Configuration::get('BLOCKPRODUCTFOOTER_IMG', $lang['id_lang']) != $file_name)
								@unlink(dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.Configuration::get('BLOCKPRODUCTFOOTER_IMG', $lang['id_lang']));

							$values['BLOCKPRODUCTFOOTER_IMG'][$lang['id_lang']] = $file_name;
						}
					}

					$update_images_values = true;
				}

				$values['BLOCKPRODUCTFOOTER_DESC'][$lang['id_lang']] = Tools::getValue('BLOCKPRODUCTFOOTER_DESC_'.$lang['id_lang']);

				$values['BLOCKPRODUCTFOOTER_EXCHANGE_TEXT'][$lang['id_lang']] = Tools::getValue('BLOCKPRODUCTFOOTER_EXCHANGE_TEXT_'.$lang['id_lang']);
			}

			if ($update_images_values)
				Configuration::updateValue('BLOCKPRODUCTFOOTER_IMG', $values['BLOCKPRODUCTFOOTER_IMG']);

			Configuration::updateValue('BLOCKPRODUCTFOOTER_DESC', $values['BLOCKPRODUCTFOOTER_DESC']);
			Configuration::updateValue('BLOCKPRODUCTFOOTER_EXCHANGE_TEXT', $values['BLOCKPRODUCTFOOTER_EXCHANGE_TEXT']);

			$this->_clearCache('blocktextproductfooter.tpl');
			return $this->displayConfirmation($this->l('The settings have been updated.'));
		}
		return '';
	}

	public function getContent()
	{
		return $this->postProcess().$this->renderForm();
	}

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'file_lang',
						'label' => $this->l('Block text product footer image'),
						'name' => 'BLOCKPRODUCTFOOTER_IMG',
						'desc' => $this->l('Upload an image for your block text product footer.'),
						'lang' => true,
					),
					array(
						'type' => 'text',
						'lang' => true,
						'label' => $this->l('Block text product footer description'),
						'name' => 'BLOCKPRODUCTFOOTER_DESC',
						'desc' => $this->l('Please enter a description for the block text product footer.')
					),
					array(
						'type' => 'text',
						'lang' => true,
						'label' => $this->l('Text to exchange in shop'),
						'name' => 'BLOCKPRODUCTFOOTER_EXCHANGE_TEXT',
						'desc' => $this->l('Please enter a text to exchange in shop.')
					)
				),
				'submit' => array(
					'title' => $this->l('Save')
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->module = $this;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitStoreConf';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'uri' => $this->getPathUri(),
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		$languages = Language::getLanguages(false);
		$fields = array();

		foreach ($languages as $lang)
		{
			$fields['BLOCKPRODUCTFOOTER_IMG'][$lang['id_lang']] = Tools::getValue('BLOCKPRODUCTFOOTER_IMG_'.$lang['id_lang'], Configuration::get('BLOCKPRODUCTFOOTER_IMG', $lang['id_lang']));
			$fields['BLOCKPRODUCTFOOTER_DESC'][$lang['id_lang']] = Tools::getValue('BLOCKPRODUCTFOOTER_DESC_'.$lang['id_lang'], Configuration::get('BLOCKPRODUCTFOOTER_DESC', $lang['id_lang']));
			$fields['BLOCKPRODUCTFOOTER_EXCHANGE_TEXT'][$lang['id_lang']] = Tools::getValue('BLOCKPRODUCTFOOTER_EXCHANGE_TEXT_'.$lang['id_lang'], Configuration::get('BLOCKPRODUCTFOOTER_EXCHANGE_TEXT', $lang['id_lang']));
		}

		return $fields;
	}
}
