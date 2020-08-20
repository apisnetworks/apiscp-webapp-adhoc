# Ad hoc Web App

This is a native module for Ad hoc on ApisCP. Native modules are shipped with ApisCP
and require little changes to work. 

## Getting started

```bash
cd /usr/local/apnscp
mkdir -p config/custom/webapps
git clone https://github.com/apisnetworks/apiscp-webapp-adhoc config/custom/webapps/adhoc
./composer dump-autoload -o
systemctl restart apiscp
```

ApisCP will restart during which time its internal caches will be updated with the new module location. 

**Happy coding!**

## License
All code within this repository has been relicensed under MIT by Apis Networks, INC.
