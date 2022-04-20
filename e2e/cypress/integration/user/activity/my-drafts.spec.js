// import { login, preserveCookie } from "../../../helper/login";

const myActivityButtonTitle = "My interests";
const myDraftMenuLabel = "My drafts";
const contentDraftTitle = "Cypress draft";
const filterTopicLabel = "Topic";
const filterTypeLabel = "Type";

context("User - Activity - My drafts", () => {
  before(() => {
    cy.login();
  });

  beforeEach(() => {
    cy.preserveCookie();
  });

  specify(
    "From HP click on my activity feed, click on my drafts and verify if I see my draft content",
    () => {
      visitMyDrafts();
      // Write in the search input our content draft title.
      cy.get(
        ".ecl-filter-sidebar__item-field .ecl-text-input.ecl-text-input--m"
      ).type(contentDraftTitle);
      cy.wait(500);
      // Check in the results div, if we have the content.
      cy.get(".ecl-teaser-overview__items").contains(contentDraftTitle);
    }
  );

  specify("My drafts should have topics and content type filter", () => {
    visitMyDrafts();
    cy.wait(500);
    cy.get(".ecl-filter-sidebar__items").contains(filterTopicLabel);
    cy.get(".ecl-filter-sidebar__items").contains(filterTypeLabel);
  });
});

const visitMyDrafts = () => {
  cy.visit("/");
  cy.contains(myActivityButtonTitle).click();
  cy.url().should("include", "/activity");
  cy.contains(myDraftMenuLabel).click();
};
