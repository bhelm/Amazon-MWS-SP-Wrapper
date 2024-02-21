# Amazon MWS to SP API Wrapper

This repository provides a convenient wrapper designed to facilitate the transition from Amazon's Marketplace Web Service (MWS) API to the Selling Partner (SP) API. It seamlessly translates MWS API calls into SP API calls and converts SP API responses back to the MWS format. This tool is particularly beneficial for users with legacy software, allowing them to continue their operations without the need for extensive codebase overhauls. The wrapper simplifies integration as many API calls are analogous across both platforms, and the structure of XML feeds remains largely unchanged. Notably, the SP API introduces stricter customer data access controls, such as distinct requests for invoice addresses, marking a significant difference from MWS.

## Disclaimer: Work in Progress

- This project is tailored to address specific requirements of my clients, covering only a subset of API calls, feeds, and options.
- Currently, the focus is on the Amazon.de marketplace. Compatibility with other marketplaces may necessitate additional adjustments.
- Originating as a proof of concept, this wrapper is in its early stages, reflecting my immediate solutions to client needs. While not extensively polished, it is shared to assist developers in avoiding the complete redevelopment of their Amazon API integration. Contributions and improvements via pull requests are welcome. However, support and troubleshooting assistance from my end may be limited.
- Anticipating the eventual deprecation of the MWS API, I plan to expand the supported API calls and enhance the wrapper's functionality in the coming weeks.
- Prior to deployment in your production environment, thorough testing with your application is advised, as there may be unimplemented parameters critical to your use case.

## Getting Started
- docker compose up -d
- docker compose exec -it php-apache composer install
- cp .env.sample .env

Then edit the .env and add your credentials. Refer to https://github.com/amazon-php/sp-api-sdk for details on the credentials and how to get them.
after that try
```
curl -v http://localhost:8800/?Action=GetFeedSubmissionList
```
And if you are lucky, you should see a xml response.

## How it Works
The integration process is straightforward: replace the hostname and protocol of your MWS API calls with http://localhost:8800/. An Apache server configured at this address intercepts requests, identifying the Action= parameter to reroute the request to a corresponding PHP file named after the action. This mechanism allows for a transparent translation between APIs. The available actions can be explored within the public directory.

