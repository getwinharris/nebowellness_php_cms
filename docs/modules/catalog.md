---
type: doc
title: Catalog Module
description: Owns product, category, consultant, campaign, and historical service-record reads from MySQL via DatabaseService.
category: module
---
# Catalog Module

Owns product, category, consultant, campaign, and historical service-record reads from MySQL via DatabaseService.

Main files: `ProductService.php`, `CategoryService.php`, `ConsultantService.php`, `CampaignPageService.php`, and the remote MySQL collections declared in `storage/schema/collections.php`. Consultant profiles and campaign pages are owner-editable and feed public editorial surfaces.

Key checks: configured image paths exist locally, slugs resolve to detail pages, and admin resource forms can edit catalog fields.
