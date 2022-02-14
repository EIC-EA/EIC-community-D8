import Template from "@theme/mails/mail-dist/template.html";
import TemplateWithBanner from "@theme/mails/mail-dist/template_02.html";
import GroupOwnership from "@theme/mails/mail-dist/group_ownership_transfer.html";
import Update from "@theme/mails/mail-dist/my_update.html";
import Tagged from "@theme/mails/mail-dist/user_tagged.html";
import FullText from "@theme/mails/mail-dist/full_text.html";


export const Base = () => Template;
export const BaseWithBanner = () => TemplateWithBanner;
export const BaseFullText = () => FullText;
export const GroupOwnershipTransfer = () => GroupOwnership;
export const UpdateNotification = () => Update;
export const TaggedNotification = () => Tagged;

export default {
  title: 'Mails/Template',
};
