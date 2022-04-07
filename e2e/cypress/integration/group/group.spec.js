// import { login, preserveCookie } from "../../helper/login";

context("User - Group", () => {
  before(() => {
    cy.login();
  });

  beforeEach(() => {
    cy.preserveCookie();
  });

  specify("Go to groups and click to Cypress public group", () => {
    cy.visit("/groups");
    cy.get(
      ".ecl-filter-sidebar__item-field .ecl-text-input.ecl-text-input--m"
    ).type("Cypress");
    cy.wait(500);
    cy.contains("Cypress [DO NOT EDIT]").click();
    cy.url().should("include", "/groups/cypress-do-not-edit");
  });

  specify("Click discussion, add comment with Bjorn user tagged", () => {
    //First go to discussions overview and click on the wanted discussion.
    cy.visit("/groups/cypress-do-not-edit/discussions");
    cy.get(
      ".ecl-filter-sidebar__item-field .ecl-text-input.ecl-text-input--m"
    ).type("DO NOT EDIT");
    cy.wait(500);
    cy.contains("Discussion [DO NOT EDIT]").click();
    cy.url().should(
      "include",
      "/cypress-do-not-edit/discussions/discussion-do-not-edit"
    );

    // Write the comment
    const currentTimestamp = Date.now();
    cy.get(".ecl-text-area.ecl-comment-form__textarea").type(
      "Automated test comment at timestamp: " + currentTimestamp
    );
    // Open the modal and search + click on Bjorn user
    cy.get(
      ".ecl-button.ecl-button--ghost.ecl-comment-form__attachment.ecl-button--as-form-option"
    ).click();
    treeWidgetSelectUser("Bjorn");
    cy.get(".popup-content").contains("Confirm").click();
    // Publish the comment
    cy.contains("Publish").click();
    // Check if the comment has been correctly create by searching with the timestamp previously saved.
    cy.contains(currentTimestamp);
  });

  specify("Invite user page, no send just form test", () => {
    cy.visit("/groups/cypress-do-not-edit");
    cy.get("a[href*=invite-members]").click();
    treeWidgetSelectUser("Bjorn");
    // Check if chip has been added with Bjorn in it
    cy.get(".MuiChip-colorPrimary").contains("Bjorn");
  });
});

function treeWidgetSelectUser(name) {
  cy.wait(500);
  cy.get(".entity-tree .MuiAutocomplete-input").type(name);
  cy.get(".MuiAutocomplete-popper").contains(name).click();
}