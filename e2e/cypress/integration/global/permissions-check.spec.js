context('Permissions', () => {
  // Post files - anonymous user
  specify('Group files overview show post content button for anonymous user', () => {
    cy.visit('/groups/cypress-do-not-edit/library')
    cy.wait(500);
    cy.get("#group-overview .ecl-collapsible-options__trigger .ecl-button__label").should('not.exist');
  })

  // Post files - Admin user
  specify('Group files overview show post content button for admin', () => {
    cy.login('admin', 'admin');
    cy.preserveCookie();
    cy.visit('/groups/cypress-do-not-edit/library')
    cy.wait(500);
    cy.scrollTo(0, 300);
    cy.get("#group-overview .ecl-collapsible-options--actions").should('exist');
  })

})


