const notAuthorizedText = 'You are not authorized to access this page.';
const notFoundText = 'The requested page could not be found.';

context('Organisation - Anonymous access', () => {
// Organisation detail page - anonymous user
  specify('Visiting organisation detail page as an anonymous user should return - you are not authorized to access this page.', () => {
    validateBannerText('/organisations/cypress-do-not-edit-organisation', notAuthorizedText)
  })
// Organisation overview page - anonymous user
  specify('Visiting organisation overview page as an anonymous user should return - you are not authorized to access this page.', () => {
    validateBannerText('/organisations', notAuthorizedText)
  })
// Test above can quickly get extended by subpage content, just by changing the URL, cause it should throw a "not authorized" on all of those pages.
// Member overview page - anonymous user (This test will fail cause the overview is vieuwable, but the people itself not)
  specify('Visiting member overview page as an anonymous user should return - you are not authorized to access this page.', () => {
    validateBannerText('/people', notAuthorizedText)
  })
// 404 banner present?
  specify('Visiting a random non existing page should return 404 with correct banner', () => {
    validateBannerText('/03cd37ff5b0b47b3b9ff5d728b823045', notFoundText)
  })
})

const validateBannerText = (visitUrl, expectedResult) => {
  cy.visit({
    url: visitUrl,
    failOnStatusCode: false
  })
  cy.get('.ecl-state-banner__title').contains(expectedResult)
}
