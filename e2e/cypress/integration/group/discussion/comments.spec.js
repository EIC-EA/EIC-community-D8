// import { login, preserveCookie } from "../../helper/login";

context("Group - discussion - comments section", () => {
  before(() => {
    cy.login();
  });

  beforeEach(() => {
    cy.preserveCookie();
  });

  specify("Show comments section in DISCUSSION page", () => {
    cy.visit("/groups/cypress-do-not-edit/discussions/discussion-cypress");
    cy.wait(500);
    cy.get('#comments-discussion').should('exist');
  });

  specify("Don't show comments section in DELETE page", () => {
    cy.visit("/groups/cypress-do-not-edit/discussions/discussion-cypress/delete");
    cy.wait(500);
    cy.get('#comments-discussion').should('not.exist');
  });

  specify("Don't show comments section in EDIT page", () => {
    cy.visit("/groups/cypress-do-not-edit/discussions/discussion-cypress/edit");
    cy.wait(500);
    cy.get('#comments-discussion').should('not.exist');
  });
});
