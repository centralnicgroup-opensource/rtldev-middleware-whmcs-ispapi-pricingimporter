ISPAPI_PRICINGIMPORTER_MODULE_VERSION := $(shell php -r 'include "ispapidpi.php"; print $$module_version;')
FOLDER := pkg/ispapi_whmcs-pricing-importer-addon-$(ISPAPI_PRICINGIMPORTER_MODULE_VERSION)

clean:
	rm -rf $(FOLDER)

buildsources:
	mkdir -p $(FOLDER)/install/modules/addons/ispapidpi
	cp *.php $(FOLDER)/install/modules/addons/ispapidpi
	cp -a templates $(FOLDER)/install/modules/addons/ispapidpi/templates
	cp -a css $(FOLDER)/install/modules/addons/ispapidpi/css
	cp HISTORY.md $(FOLDER)
	cp HISTORY.old $(FOLDER)
	cp README.pdf $(FOLDER)
	cp LICENSE $(FOLDER)
	cp CONTRIBUTING.md $(FOLDER)

buildlatestzip:
	cp pkg/ispapi_whmcs-pricing-importer-addon.zip ./ispapi_whmcs-pricing-importer-addon-latest.zip # for downloadable "latest" zip by url

zip:
	rm -rf pkg/ispapi_whmcs-pricing-importer-addon.zip
	@$(MAKE) buildsources
	cd pkg && zip -r ispapi_whmcs-pricing-importer-addon.zip ispapi_whmcs-pricing-importer-addon-$(ISPAPI_PRICINGIMPORTER_MODULE_VERSION)
	@$(MAKE) clean

tar:
	rm -rf pkg/ispapi_whmcs-pricing-importer-addon.tar.gz
	@$(MAKE) buildsources
	cd pkg && tar -zcvf ispapi_whmcs-pricing-importer-addon.tar.gz ispapi_whmcs-pricing-importer-addon-$(ISPAPI_PRICINGIMPORTER_MODULE_VERSION)
	@$(MAKE) clean

allarchives:
	rm -rf pkg/ispapi_whmcs-pricing-importer-addon.zip
	rm -rf pkg/ispapi_whmcs-pricing-importer-addon.tar
	@$(MAKE) buildsources
	cd pkg && zip -r ispapi_whmcs-pricing-importer-addon.zip ispapi_whmcs-pricing-importer-addon-$(ISPAPI_PRICINGIMPORTER_MODULE_VERSION) && tar -zcvf ispapi_whmcs-pricing-importer-addon.tar.gz ispapi_whmcs-pricing-importer-addon-$(ISPAPI_PRICINGIMPORTER_MODULE_VERSION)
	@$(MAKE) buildlatestzip
	@$(MAKE) clean