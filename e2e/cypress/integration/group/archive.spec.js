// import { login, preserveCookie } from "../../helper/login";

context("Group - archive", () => {
  before(() => {
    cy.login();
  });

  beforeEach(() => {
    cy.preserveCookie();
  });

  specify("Have a tag archived", () => {
    cy.visit("/groups/cypress-archive-do-not-edit");
    cy.get(".ecl-tag").contains(
      "Archived"
    ).should("exist");
  });

  specify("Have a banner state in the archived group", () => {
    cy.visit("/groups/cypress-archive-do-not-edit");
    cy.get(".ecl-state-banner__title").contains(
      "Group archived"
    ).should("exist");
  });

  specify("Can follow archived group", () => {
    cy.visit("/groups/cypress-archive-do-not-edit");
    cy.get('a[href*="flag/flag/follow_group"]').should('exist');
  });

  specify("Can like archived group", () => {
    cy.visit("/groups/cypress-archive-do-not-edit");
    cy.get('a[href*="flag/flag/recommend_group"]').should('exist');
  });

  specify("Can share archived group", () => {
    cy.visit("/groups/cypress-archive-do-not-edit");
    cy.get('.ecl-social-media-share').should('exist');
  });

  specify("Can navigate in archived group", () => {
    cy.visit("/groups/cypress-archive-do-not-edit");
    cy.get('.ecl-subnavigation__items').children().should('have.length', 4);
  });

  specify("Can navigate in archived group (discussion)", () => {
    cy.visit("/groups/cypress-archive-do-not-edit/discussions");
    cy.get('.ecl-subnavigation__item a[href*="discussions"]').parent().should('have.class', "ecl-subnavigation__item--is-active");
  });
});
