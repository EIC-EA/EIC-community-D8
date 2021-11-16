import {store} from 'react-notifications-component';

export default function notifiy(message, level = 'success') {
    store.addNotification({
        message,
        type: level,
        insert: "top",
        container: "bottom-right",
        dismiss: {
            duration: 5000,
            onScreen: true
        },
        animationIn: ["animate__animated", "animate__fadeIn"],
        animationOut: ["animate__animated", "animate__fadeOut"],
    });
}
