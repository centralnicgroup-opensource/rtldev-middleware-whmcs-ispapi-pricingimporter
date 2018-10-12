ISPAPI_PRICINGIMPORTER_MODULE_VERSION := $(shell php -r 'include "ispapidpi.php"; print $$module_version;')
FOLDER := pkg/whmcs-ispapi-pricingimporter-$(ISPAPI_PRICINGIMPORTER_MODULE_VERSION)

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
	cp pkg/whmcs-ispapi-pricingimporter.zip ./whmcs-ispapi-pricingimporter-latest.zip # for downloadable "latest" zip by url

zip:
	rm -rf pkg/whmcs-ispapi-pricingimporter.zip
	@$(MAKE) buildsources
	cd pkg && zip -r whmcs-ispapi-pricingimporter.zip whmcs-ispapi-pricingimporter-$(ISPAPI_PRICINGIMPORTER_MODULE_VERSION)
	@$(MAKE) clean

tar:
	rm -rf pkg/whmcs-ispapi-pricingimporter.tar.gz
	@$(MAKE) buildsources
	cd pkg && tar -zcvf whmcs-ispapi-pricingimporter.tar.gz whmcs-ispapi-pricingimporter-$(ISPAPI_PRICINGIMPORTER_MODULE_VERSION)
	@$(MAKE) clean

allarchives:
	rm -rf pkg/whmcs-ispapi-pricingimporter.zip
	rm -rf pkg/whmcs-ispapi-pricingimporter.tar
	@$(MAKE) buildsources
	cd pkg && zip -r whmcs-ispapi-pricingimporter.zip whmcs-ispapi-pricingimporter-$(ISPAPI_PRICINGIMPORTER_MODULE_VERSION) && tar -zcvf whmcs-ispapi-pricingimporter.tar.gz whmcs-ispapi-pricingimporter-$(ISPAPI_PRICINGIMPORTER_MODULE_VERSION)
	@$(MAKE) buildlatestzip
	@$(MAKE) clean
