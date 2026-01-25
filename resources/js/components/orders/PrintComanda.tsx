import { forwardRef, useImperativeHandle } from 'react';

import { Order, OrderItemOption } from '@/types';

interface DeliveryAddressSnapshot {
    id?: number;
    customer_id?: number;
    label?: string;
    address_line?: string;
    latitude?: number;
    longitude?: number;
    delivery_notes?: string;
    zone?: string;
    is_default?: boolean;
    created_at?: string;
    updated_at?: string;
}

interface PrintComandaProps {
    order: Order & {
        delivery_address?: string | DeliveryAddressSnapshot | null;
        restaurant_name?: string | null;
    };
}

export interface PrintComandaHandle {
    print: () => void;
}

const getDeliveryAddressText = (address: string | DeliveryAddressSnapshot | null | undefined): string | null => {
    if (!address) return null;
    if (typeof address === 'string') return address;
    const parts: string[] = [];
    if (address.label) parts.push(address.label);
    if (address.address_line) parts.push(address.address_line);
    if (address.delivery_notes) parts.push(`Notas: ${address.delivery_notes}`);
    return parts.length > 0 ? parts.join(' - ') : null;
};

const formatDateTime = (dateString: string): string => {
    return new Date(dateString).toLocaleString('es-GT', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'America/Guatemala',
    });
};

const formatSubwayCard = (card: string | null | undefined): string | null => {
    if (!card) return null;
    if (card.length === 11) {
        return `${card.slice(0, 4)}-${card.slice(4, 8)}-${card.slice(8)}`;
    }
    return card;
};

const groupOptionsBySection = (options: OrderItemOption[]): Record<string, string[]> => {
    const grouped: Record<string, string[]> = {};
    for (const option of options) {
        const sectionName = option.section_name || 'Opciones';
        if (!grouped[sectionName]) {
            grouped[sectionName] = [];
        }
        grouped[sectionName].push(option.name);
    }
    return grouped;
};

/**
 * Genera el HTML completo para imprimir la comanda en una ventana nueva
 */
const generatePrintHTML = (order: PrintComandaProps['order']): string => {
    const serviceTypeLabel = order.service_type === 'delivery' ? 'DELIVERY' : 'PARA LLEVAR';

    let itemsHTML = '';
    order.items?.forEach((item) => {
        const groupedOptions = item.options ? groupOptionsBySection(item.options) : {};
        const basePrice = Number(item.unit_price) || 0;
        const extrasTotal = Number(item.options_price) || 0;
        const totalPrice = Number(item.total_price) || 0;

        let optionsHTML = '';
        Object.entries(groupedOptions).forEach(([sectionName, optionNames]) => {
            optionsHTML += `<div style="font-size:10px;margin:2px 0;"><b>${sectionName}:</b> ${optionNames.join(', ')}</div>`;
        });

        const notesHTML = item.notes
            ? `<div style="margin-left:30px;margin-top:4px;font-size:11px;font-weight:bold;padding:4px;border:1px solid #000;">*** NOTA: ${item.notes} ***</div>`
            : '';

        const extrasRow = extrasTotal > 0
            ? `<div style="display:flex;justify-content:space-between;margin:1px 0;"><span>Extras:</span><span>Q${extrasTotal.toFixed(2)}</span></div>`
            : '';

        // Construir el nombre completo del producto para mostrar en precios
        const productDisplayName = item.variant ? `${item.name} ${item.variant}` : item.name;
        const quantityLabel = item.quantity > 1 ? ` x${item.quantity}` : '';

        itemsHTML += `
            <div style="margin-bottom:8px;padding-bottom:6px;border-bottom:1px dotted #999;">
                <div style="display:flex;align-items:flex-start;gap:6px;">
                    <span style="font-size:18px;font-weight:bold;min-width:24px;text-align:center;border:1px solid #000;padding:1px 3px;">${item.quantity}</span>
                    <div style="flex:1;">
                        ${item.category ? `<div style="font-size:12px;font-weight:bold;text-transform:uppercase;">${item.category}</div>` : ''}
                        <div style="font-size:12px;font-weight:bold;text-transform:uppercase;">${item.name}${item.variant ? ` - ${item.variant}` : ''}</div>
                    </div>
                </div>
                ${optionsHTML ? `<div style="margin-left:30px;margin-top:3px;">${optionsHTML}</div>` : ''}
                ${notesHTML}
                <div style="margin-left:30px;margin-top:4px;padding-top:3px;border-top:1px dotted #999;font-size:10px;">
                    <div style="display:flex;justify-content:space-between;margin:1px 0;">
                        <span>${productDisplayName}${quantityLabel}:</span>
                        <span>Q${basePrice.toFixed(2)}</span>
                    </div>
                    ${extrasRow}
                    <div style="display:flex;justify-content:space-between;margin:1px 0;font-weight:bold;border-top:1px solid #000;padding-top:2px;margin-top:2px;">
                        <span>TOTAL:</span>
                        <span>Q${totalPrice.toFixed(2)}</span>
                    </div>
                </div>
            </div>
        `;
    });

    const customerHTML = order.customer ? `
        <div style="display:flex;justify-content:space-between;margin:1px 0;font-size:10px;">
            <span>Cliente:</span>
            <span>${order.customer.full_name}</span>
        </div>
        ${order.customer.phone ? `<div style="display:flex;justify-content:space-between;margin:1px 0;font-size:10px;"><span>Tel:</span><span>${order.customer.phone}</span></div>` : ''}
        ${(order.customer as any).subway_card ? `<div style="display:flex;justify-content:space-between;margin:1px 0;font-size:10px;"><span>SubwayCard:</span><span style="font-family:'Courier New',monospace;font-weight:bold;">${formatSubwayCard((order.customer as any).subway_card)}</span></div>` : ''}
    ` : '';

    const notesHTML = order.notes ? `
        <div style="border:1px solid #000;padding:5px;margin-bottom:6px;font-size:10px;">
            <div style="font-weight:bold;margin-bottom:2px;">NOTAS DEL PEDIDO:</div>
            <div>${order.notes}</div>
        </div>
    ` : '';

    const deliveryHTML = order.service_type === 'delivery' && order.delivery_address ? `
        <div style="border:1px solid #000;padding:5px;margin-bottom:6px;font-size:10px;">
            <div style="font-weight:bold;margin-bottom:2px;">DIRECCION DE ENTREGA:</div>
            <div>${getDeliveryAddressText(order.delivery_address)}</div>
        </div>
    ` : '';

    const restaurantName = (order as any).restaurant_name || '';

    return `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comanda #${order.order_number}</title>
    <style>
        @page {
            size: 72mm auto;
            margin: 3mm 4mm;
        }
        @media print {
            html, body {
                width: 72mm;
                margin: 0;
                padding: 0;
            }
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            background: #fff;
            width: 72mm;
            padding: 2mm;
        }
    </style>
</head>
<body>
    <div style="text-align:center;border-bottom:1px dashed #000;padding-bottom:6px;margin-bottom:6px;">
        ${restaurantName ? `<p style="font-size:14px;font-weight:bold;margin:0 0 4px 0;text-transform:uppercase;">${restaurantName}</p>` : ''}
        <p style="font-size:16px;font-weight:bold;margin:0;">COMANDA</p>
        <p style="font-size:22px;font-weight:bold;margin:3px 0;">#${order.order_number}</p>
        <span style="font-size:14px;font-weight:bold;padding:3px 6px;border:1px solid #000;display:inline-block;margin:3px 0;">${serviceTypeLabel}</span>
    </div>

    <div style="border-bottom:1px dashed #000;padding-bottom:6px;margin-bottom:6px;">
        <div style="display:flex;justify-content:space-between;margin:1px 0;font-size:10px;">
            <span>Fecha:</span>
            <span>${formatDateTime(order.created_at)}</span>
        </div>
        ${customerHTML}
    </div>

    <div style="border-bottom:1px dashed #000;padding-bottom:6px;margin-bottom:6px;">
        ${itemsHTML}
    </div>

    <div style="border-bottom:1px dashed #000;padding-bottom:6px;margin-bottom:6px;">
        <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:bold;">
            <span>SUMA TOTAL</span>
            <span>Q${(Number(order.total) || 0).toFixed(2)}</span>
        </div>
    </div>

    ${notesHTML}
    ${deliveryHTML}

    <div style="text-align:center;font-size:9px;margin-top:6px;padding-top:6px;border-top:1px dashed #000;">
        <p>Impreso: ${formatDateTime(new Date().toISOString())}</p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>`;
};

/**
 * Función para imprimir una orden directamente (abre en nueva pestaña)
 */
export const printOrder = (order: PrintComandaProps['order']): void => {
    // Usar nombre único para cada orden para permitir múltiples pestañas
    const windowName = `print_order_${order.id || order.order_number}_${Date.now()}`;
    const printWindow = window.open('', windowName);
    if (printWindow) {
        printWindow.document.open();
        printWindow.document.write(generatePrintHTML(order));
        printWindow.document.close();
    }
};

/**
 * Componente PrintComanda - NO renderiza nada visible
 * Expone método print() a través de ref
 */
export const PrintComanda = forwardRef<PrintComandaHandle, PrintComandaProps>(({ order }, ref) => {
    useImperativeHandle(ref, () => ({
        print: () => printOrder(order),
    }));

    // No renderiza nada - la impresión se hace en ventana nueva
    return null;
});

PrintComanda.displayName = 'PrintComanda';

export default PrintComanda;
