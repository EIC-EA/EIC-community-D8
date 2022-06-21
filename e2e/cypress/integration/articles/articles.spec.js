context('News/stories - Anonymous user', () => {
  specify('Do not show news/stories from organisation to anonymous user', () => {
    cy.visit("/articles");
    cy.get(
      ".ecl-filter-sidebar__item-field .ecl-text-input.ecl-text-input--m"
    ).type("Cypress");
    cy.wait(500);
    cy.contains("Cypress - news organisation").should('not.exist')
  })
})
