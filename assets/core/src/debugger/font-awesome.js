/* import the fontawesome core */
import { library } from '@fortawesome/fontawesome-svg-core';
import { faRotateRight } from '@fortawesome/free-solid-svg-icons/faRotateRight';
import { faArrowsRotate } from '@fortawesome/free-solid-svg-icons/faArrowsRotate';
import { faClipboard } from '@fortawesome/free-solid-svg-icons/faClipboard';
import { faExternalLink } from '@fortawesome/free-solid-svg-icons/faExternalLink';
import { faEye } from '@fortawesome/free-solid-svg-icons/faEye';
import { faLink } from '@fortawesome/free-solid-svg-icons/faLink';

/* import specific icons */
import { faList } from '@fortawesome/free-solid-svg-icons/faList';

/* add icons to the library */
library.add(
  faEye,
  faExternalLink,
  faList,
  faArrowsRotate,
  faClipboard,
  faLink,
  faRotateRight
);
