---
type: doc
title: Home Page
description: Route / - Nebo clinic landing page with programs, practitioners, care principles, and consultation enquiry actions.
category: page
---
# Home Page

Route: `/`

Controller: `PublicController@home`

Purpose: present Nebo programs, practitioners, care principles, and a clear consultation enquiry journey.

Key checks: hero and program actions reach `/contact` or `/#programs`; program and practitioner rails work with touch, keyboard, and arrows; placeholder portraits remain editable in Admin.
The first viewport uses the remote catalog and the Varahi Amman image carousel. Product and category data come from remote MySQL through `DatabaseService`; missing required category identity fields never create blank cards or terminate the page.
