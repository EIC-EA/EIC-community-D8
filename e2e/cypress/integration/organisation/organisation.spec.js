let organisationUrl = '';

context("Organisation - Create organisation as DA", () => {
  before(() => {
    cy.login('admin', 'admin');
  });

  beforeEach(() => {
    cy.preserveCookie();
  });

  specify("Go to the create organisation form and save it", () => {
    cy.visit("/group/add/organisation").then(() => {
      cy.get('#edit-label-0-value').type('testttt');
      cy.wait(2000)
      cy.fillCkeditor('edit-field-body-0-value', 'HTML body')

      cy.get('#edit-field-organisation-type-153').click();
      cy.get('.horizontal-tab-button-2').click();

      cy.get("#edit-field-vocab-services-products-wrapper .MuiAutocomplete-inputFocused").type("Quality ass");
      cy.get(".MuiAutocomplete-popper").contains("Quality ass").click();
      cy.get("#edit-field-vocab-target-markets-wrapper .MuiAutocomplete-inputFocused").type("Biotechnology");
      cy.get(".MuiAutocomplete-popper").contains("Biotechnology").click();

      cy.get('.horizontal-tab-button-5 .form-required').click();

      cy.get("#edit-field-vocab-topics-wrapper .MuiAutocomplete-inputFocused").type("security");
      cy.get(".MuiAutocomplete-popper").contains("Security").click();

      cy.get('#edit-moderation-state-0-state').select('published');

      cy.get('.vertical-tabs__menu-item.last').click()
      cy.get('#edit-path-0-pathauto').click()
      organisationUrl = '/organisations/cypress-' + Date.now()
      cy.get('#edit-path-0-alias').clear().type(organisationUrl)
      cy.get('#edit-submit').click()

      cy.url().should('contains', '/organisations/cypress-');
    });
  });

  specify("Edit the organisation and enable all features", () => {
    cy.visit(organisationUrl + '/edit').then(() => {
      cy.get('.horizontal-tab-button-1').click()
      cy.get('#edit-features [type="checkbox"]').check()
      cy.get('#edit-submit').click()
      cy.url().should('contains', '/organisations/cypress-');

      cy.visit(organisationUrl)
      cy.get(".ecl-subnavigation__items").contains("Events").should("exist");
    });
  });

  specify("Invite cypress/cypress_ga users", () => {
    cy.visit(organisationUrl + '/invite-members')
    cy.entityTreeSelect('Cypress Ga')
    cy.entityTreeSelect('Cypress Automated test')
    cy.get('#edit-submit').click()
    cy.wait(1000)
    cy.get('#edit-submit').click()
    cy.wait(5000)
  });
});


context("Organisation - Accept invitations with cypress/cypress_ga", () => {
  specify("Accept as cypress user", () => {
    cy.login('cypress', 'cypress')

    cy.visit('/users/cypress-automated-test/invitations').then(() => {
      cy.get(".dropbutton-action a[href*=accept]").each(($el, index, $list) => {
        const link = $el.attr('href');
        cy.visit(link).then(() => {
          cy.get('#edit-submit').click()
        });
      });
    });
  });

  specify("Accept as cypress_ga user", () => {
    cy.login('cypress_ga', 'cypress')

    cy.visit('/users/cypress-ga/invitations').then(() => {
      cy.get(".dropbutton-action a[href*=accept]").each(($el, index, $list) => {
        const link = $el.attr('href');
        cy.visit(link).then(() => {
          cy.get('#edit-submit').click()
        });
      });
    });
  });
});

context("Organisation - Set cypress_ga admin", () => {
  specify("Promote cypress_ga to GA", () => {
    cy.login('admin', 'admin');
    cy.visit(organisationUrl + '/members').then(() => {
      let test = cy.get('.ecl-table_body .views-field-name a[href*=cypress-ga]')
      test = test.parent('.views-field.views-field-name').parent('.ecl-table__row')
      test.find('.edit.dropbutton-action').click()
      cy.get('.js-form-item-group-roles-organisation-admin .ecl-checkbox__box').click()
      cy.get('#edit-submit').click()
    });
  });
});

context("Organisation - As cypress_ga, add content", () => {
  specify("Add news", () => {
    cy.login('cypress_ga', 'cypress');
    cy.visit(organisationUrl).then(() => {
      cy.get('.ecl-collapsible-options__trigger-wrapper').contains('Add content').click()
      cy.contains('Add News').click()

      cy.get('#edit-title-0-value').type('Test news');
      cy.fillCkeditor('edit-field-body-0-value', 'Test body')
      cy.chooseMedia('#edit-field-header-visual-open-button')
      cy.fillCkeditor('edit-field-introduction-0-value', 'Test intro')
      cy.chooseMedia('#edit-field-image-open-button')

      cy.get('.horizontal-tab-button-0').scrollIntoView()
      cy.get('.horizontal-tab-button-4 a').click({force: true})

      cy.get(".form-item-field-vocab-topics .MuiAutocomplete-inputFocused").type("Biotechnology");
      cy.get(".MuiAutocomplete-popper").contains("Biotechnology").click();

      cy.get(".form-item-field-vocab-geo .MuiAutocomplete-inputFocused").type("Belgium");
      cy.get(".MuiAutocomplete-popper").contains("Belgium").first().click();
      cy.get("#edit-moderation-state-published").click({force: true});
    });
  });

  specify("Add event", () => {
    cy.login('cypress_ga', 'cypress');
    cy.visit(organisationUrl).then(() => {
      cy.get('.ecl-collapsible-options__trigger-wrapper').contains('Add content').click()
      cy.get('#block-eic-group-header .ecl-editorial-header__wrapper .ecl-collapsible-options__collapse-wrapper').contains('Add Event').click()

      cy.get('#edit-title-0-value').type('Test event');
      cy.get('#edit-field-location-type').scrollIntoView();
      cy.get('#edit-field-location-type .ecl-checkbox__box').first().click()

      cy.get('#edit-field-vocab-event-type-wrapper').scrollIntoView()
      cy.get('#edit-field-vocab-event-type').select('152');

      cy.get('.field--name-field-language #entities-tree--search').type('Danish')
      cy.get(".MuiAutocomplete-popper").contains("Danish").click();

      cy.get('#edit-moderation-state-0-state').select('published');

      cy.get('.horizontal-tab-button-0').scrollIntoView()
      cy.get('.horizontal-tab-button-3 a').click({force: true})

      cy.get(".form-item-field-vocab-topics .MuiAutocomplete-inputFocused").type("Biotechnology");
      cy.get(".MuiAutocomplete-popper").contains("Biotechnology").click();

      cy.get("#edit-submit").click({force: true});
    });
  });
});

context("Organisation - As GM verify that he has access to news/event created", () => {
  before(() => {
    cy.login('cypress', 'cypress');
  });

  beforeEach(() => {
    cy.preserveCookie();
  });

  specify("Check GM can see news in detail page and access to the page", () => {
    cy.visit(organisationUrl).then(() => {
      cy.get('#news').scrollIntoView()
      cy.get('#news .ecl-teaser__title a').click()
      cy.url().should('contains', '/news/test-news');
    });
  });

  specify("Check GM can see news in overview", () => {
    cy.visit(organisationUrl + '/news').then(() => {
      cy.get('.ecl-teaser-overview__items').contains('Test news')
    });
  });

  specify("Check GM can see event in overview", () => {
    cy.visit(organisationUrl + '/events').then(() => {
      cy.get('.ecl-teaser-overview__items').contains('Test event')
    });
  });
});

context("Organisation - As DA delete organisation", () => {
  specify("Delete organisation", () => {
    cy.login('admin', 'admin');
    cy.visit(organisationUrl + '/delete').then(() => {
      cy.get('#edit-submit').click()
    });
  });
});
