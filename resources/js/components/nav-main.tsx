import { Collapsible, CollapsibleContent } from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuAction,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import React from 'react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage();
    const { state } = useSidebar();
    const [clickedGroup, setClickedGroup] = React.useState<string | null>(null);

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Menú</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    if (item.items && item.items.length > 0) {
                        return <GroupItem key={item.title} item={item} clickedGroup={clickedGroup} setClickedGroup={setClickedGroup} />;
                    }

                    // Item normal sin subitems
                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={item.href ? page.url.startsWith(item.href) : false}
                                tooltip={state === 'collapsed' ? { children: item.title } : undefined}
                            >
                                <Link href={item.href || '#'} prefetch className="flex min-w-0 items-center">
                                    {item.icon && <item.icon className="mr-2 h-4 w-4 flex-shrink-0" />}
                                    <span className="truncate overflow-hidden text-ellipsis whitespace-nowrap">{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}

function GroupItem({
    item,
    clickedGroup,
    setClickedGroup,
}: {
    item: NavItem;
    clickedGroup: string | null;
    setClickedGroup: (group: string | null) => void;
}) {
    const page = usePage();
    const { state, setOpen: setSidebarOpen } = useSidebar();

    const isSubItemActive = item.items?.some((subItem) => subItem.href && page.url.startsWith(subItem.href));

    // Solo abrir inicialmente si tiene items activos Y la sidebar está expandida
    const [open, setOpen] = React.useState<boolean>(() => state === 'expanded' && !!isSubItemActive);

    // Solo mantener abierto si tiene items activos, pero no auto-abrir cuando sidebar se expande
    React.useEffect(() => {
        if (isSubItemActive && state === 'expanded') {
            // Solo abrir automáticamente si este grupo fue clickeado recientemente
            if (clickedGroup === item.title) {
                setOpen(true);
            }
        } else if (state === 'collapsed') {
            // Cerrar todos los grupos cuando la sidebar se colapsa
            setOpen(false);
        }
    }, [isSubItemActive, state, clickedGroup, item.title]);

    const handleItemClick = () => {
        if (state === 'collapsed') {
            // Marcar este grupo como el clickeado
            setClickedGroup(item.title);
            // Si está cerrada, expandir sidebar y abrir el grupo
            setSidebarOpen(true);
            setOpen(true);
        } else {
            // Si está abierta, solo toggle el grupo
            setOpen(!open);
            // Limpiar el grupo clickeado si se está cerrando
            if (open) {
                setClickedGroup(null);
            }
        }
    };

    return (
        <Collapsible key={item.title} open={open} onOpenChange={setOpen} className="group/collapsible">
            <SidebarMenuItem>
                <SidebarMenuButton
                    isActive={!!isSubItemActive}
                    onClick={handleItemClick}
                    tooltip={state === 'collapsed' ? { children: item.title } : undefined}
                    className="flex min-w-0 items-center"
                >
                    {item.icon && <item.icon className="mr-2 h-4 w-4 flex-shrink-0" />}
                    <span className="flex-1 truncate overflow-hidden text-ellipsis whitespace-nowrap">{item.title}</span>
                </SidebarMenuButton>

                <SidebarMenuAction aria-expanded={open} onClick={handleItemClick}>
                    <ChevronRight className={`text-foreground transition-transform duration-200 ${open ? 'rotate-90' : ''}`} />
                </SidebarMenuAction>

                <CollapsibleContent>
                    <SidebarMenuSub>
                        {item.items?.map((subItem) => (
                            <SidebarMenuSubItem key={subItem.title}>
                                <SidebarMenuSubButton asChild isActive={subItem.href ? page.url.startsWith(subItem.href) : false}>
                                    <Link href={subItem.href || '#'} prefetch className="flex min-w-0 items-center">
                                        <span className="truncate overflow-hidden text-ellipsis whitespace-nowrap">{subItem.title}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        ))}
                    </SidebarMenuSub>
                </CollapsibleContent>
            </SidebarMenuItem>
        </Collapsible>
    );
}
