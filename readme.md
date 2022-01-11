# Unofficial RARBG Synology Download Station BTSearch
This is a "wokring" Proof-of-concept script to add the RARBG BTSearch to Synology Download Station. I was looking around and didn't find any working plugins that search through all the categories properly so I made my own. 

During development I realized that RARBG has a captcha that checks for bots and this can be sorta bypassed by cookies after you completed the challenge hence the quotes on my previous sentence of working POC. Code is released with my current cookies but it may not work if you are trying in the future.

Obviously if the host link that is packed is blocked in your country you can change it with one of the available proxies and pack the module yourself.

You could update the cookies and pack again the module at any time. I included the file `pack.sh` whihch is just a tar zcf command anyway. The module itself is a compressed tar containing INFO and search.php. You can find more by looking at the [official documentation](https://global.download.synology.com/download/Document/Software/DeveloperGuide/Package/DownloadStation/All/enu/DLM_Guide.pdf) released by Synology.

## Requirements
- `php-dom` module used to parse the html of the search responses

## Download
Available as a release here: https://github.com/seanwlk/synology-rarbg-dlm/releases/latest

### Tested on
- Synology DS220+ - DSM 7.0.1-42218