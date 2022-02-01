import signupTemplate from '@theme/patterns/components/signup.html.twig';
import common from '@theme/data/common.data';

const data = {
  title: "Sign up to this event",
  text: "Get notified of the latest changes and take part in the discussion linked to this event.",
  action: {
    label: "Sign up now",
    url: "https://google.be"
  }
}

export const Base = () => signupTemplate(data);

export default {
  title: 'Components / Signup',
};
